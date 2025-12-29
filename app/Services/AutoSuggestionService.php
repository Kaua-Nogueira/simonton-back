<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Transaction;
use Illuminate\Support\Str;

class AutoSuggestionService
{
    public function suggestMemberAndCategory(Transaction $transaction): array
    {
        $suggestion = [
            'member_id' => null,
            'category_id' => null,
            'cost_center_id' => null,
            'confidence' => 0,
        ];

        // Extract CPF from PIX description
        $cpf = $this->extractCPFFromDescription($transaction->description);
        
        if ($cpf) {
            $member = Member::where('cpf', $cpf)->first();
            
            if ($member) {
                $suggestion['member_id'] = $member->id;
                $suggestion['confidence'] = 90;
                
                // Check historical transactions for this member
                $historicalCategory = $this->findHistoricalCategory($member->id, $transaction->type);
                
                if ($historicalCategory) {
                    $suggestion['category_id'] = $historicalCategory->category_id;
                    $suggestion['cost_center_id'] = $historicalCategory->cost_center_id;
                    $suggestion['confidence'] = 95;
                }
            }
        }

        // Pattern matching in description
        if (!$suggestion['category_id']) {
            $categoryMatch = $this->matchCategoryByDescription($transaction->description);
            if ($categoryMatch) {
                $suggestion['category_id'] = $categoryMatch['category_id'];
                $suggestion['confidence'] = max($suggestion['confidence'], $categoryMatch['confidence']);
            }
        }

        return $suggestion;
    }

    private function extractCPFFromDescription(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        // Match CPF pattern (11 digits)
        preg_match('/\d{11}/', $description, $matches);
        
        return $matches[0] ?? null;
    }

    private function findHistoricalCategory(int $memberId, string $type): ?object
    {
        return Transaction::where('member_id', $memberId)
            ->where('type', $type)
            ->whereNotNull('category_id')
            ->where('status', 'confirmed')
            ->select('category_id', 'cost_center_id')
            ->groupBy('category_id', 'cost_center_id')
            ->orderByRaw('COUNT(*) DESC')
            ->first();
    }

    private function matchCategoryByDescription(string $description): ?array
    {
        $patterns = [
            'dizimo' => ['keywords' => ['dizimo', 'dízimo'], 'confidence' => 85],
            'oferta' => ['keywords' => ['oferta'], 'confidence' => 85],
            'energia' => ['keywords' => ['energia', 'eletric', 'light'], 'confidence' => 80],
            'agua' => ['keywords' => ['agua', 'água', 'saneamento'], 'confidence' => 80],
            'salario' => ['keywords' => ['salario', 'salário', 'pagamento'], 'confidence' => 75],
        ];

        $descriptionLower = Str::lower($description);

        foreach ($patterns as $categorySlug => $pattern) {
            foreach ($pattern['keywords'] as $keyword) {
                if (Str::contains($descriptionLower, $keyword)) {
                    // In real implementation, fetch category by slug/name
                    return [
                        'category_id' => null, // Would lookup by slug
                        'confidence' => $pattern['confidence'],
                    ];
                }
            }
        }

        return null;
    }
}
