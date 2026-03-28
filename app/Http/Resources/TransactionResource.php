<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'date' => $this->date->format('Y-m-d'),
            'paymentMethod' => $this->payment_method,
            'status' => $this->status,
            'suggestionConfidence' => $this->suggestion_confidence,
            'hasHighConfidence' => $this->hasHighConfidence(),
            'member_id' => $this->member_id,
            'category_id' => $this->category_id,
            'cost_center_id' => $this->cost_center_id,
            'society_id' => $this->society_id,
            'balanceBefore' => $this->balance_before ? (float) $this->balance_before : null,
            'balanceAfter' => $this->balance_after ? (float) $this->balance_after : null,
            'bank_transaction_id' => $this->bank_transaction_id,
            'reconciledAt' => $this->reconciled_at?->toISOString(),
            'member' => $this->whenLoaded('member', fn() => $this->member ? new MemberResource($this->member) : null),
            'category' => $this->whenLoaded('category', fn() => $this->category ? new CategoryResource($this->category) : null),
            'costCenter' => $this->whenLoaded('costCenter', fn() => $this->costCenter ? new CostCenterResource($this->costCenter) : null),
            'society' => $this->whenLoaded('society', fn() => $this->society ? new SocietyResource($this->society) : null),
            'reconciledBy' => $this->whenLoaded('reconciledBy', fn() => $this->reconciledBy ? [
                'id' => $this->reconciledBy->id,
                'name' => $this->reconciledBy->name,
            ] : null),
            'splitTransactions' => $this->whenLoaded('splitTransactions', fn() => TransactionSplitResource::collection($this->splitTransactions)),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'reconciled_at' => $this->reconciled_at?->toISOString(),
        ];
    }
}
