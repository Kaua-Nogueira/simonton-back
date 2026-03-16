<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cpf' => $this->cpf,
            'formattedCpf' => $this->formatted_cpf,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zipCode' => $this->zip_code,
            'status' => $this->status,
            'birthDate' => $this->birth_date?->format('Y-m-d'),
            'rollNumber' => $this->roll_number,
            'admissionDate' => $this->admission_date?->format('Y-m-d'),
            'admissionType' => $this->admission_type,
            'previousChurch' => $this->previous_church,
            'dismissalDate' => $this->dismissal_date?->format('Y-m-d'),
            'dismissalType' => $this->dismissal_type,
            'destinationChurch' => $this->destination_church,
            'transactionsCount' => $this->when(isset($this->transactions_count), $this->transactions_count),
            'totalContributions' => $this->when(isset($this->total_contributions), (float) $this->total_contributions),
            'lastContribution' => $this->last_contribution,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
            'roles' => $this->whenLoaded('roles'),
            'user' => $this->whenLoaded('user'),
        ];
    }
}
