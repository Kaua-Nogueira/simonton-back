<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class OFXImportService
{
    public function import(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $transactions = [];

        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches);

        foreach ($matches[1] as $match) {
            $amount = $this->extractTag($match, 'TRNAMT');
            $date = $this->extractTag($match, 'DTPOSTED');
            $description = $this->extractTag($match, 'MEMO') ?? $this->extractTag($match, 'NAME');

            if ($amount && $date) {
                $transactions[] = [
                    'type' => $amount > 0 ? 'income' : 'expense',
                    'amount' => abs($amount),
                    'description' => $description,
                    'date' => $this->parseOFXDate($date),
                    'payment_method' => $this->detectPaymentMethod($description),
                    'status' => 'pending',
                    'ofx_data' => $match,
                ];
            }
        }

        return $transactions;
    }

    private function extractTag(string $content, string $tag): ?string
    {
        preg_match("/<{$tag}>(.*?)(?:<\/{$tag}>|\n)/", $content, $matches);
        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    private function parseOFXDate(string $date): string
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        
        return "{$year}-{$month}-{$day}";
    }

    private function detectPaymentMethod(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        $description = strtolower($description);

        if (str_contains($description, 'pix')) {
            return 'pix';
        }
        if (str_contains($description, 'ted')) {
            return 'ted';
        }
        if (str_contains($description, 'boleto')) {
            return 'boleto';
        }
        if (str_contains($description, 'cartao') || str_contains($description, 'cartão')) {
            return 'cartao';
        }

        return 'outros';
    }
}
