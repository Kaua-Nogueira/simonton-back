<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionSplitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transactionId' => $this->transaction_id,
            'amount' => (float) $this->amount,
            'notes' => $this->notes,
            'category' => $this->whenLoaded('category', fn() => $this->category ? new CategoryResource($this->category) : null),
            'costCenter' => $this->whenLoaded('costCenter', fn() => $this->costCenter ? new CostCenterResource($this->costCenter) : null),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
        ];
    }
}
