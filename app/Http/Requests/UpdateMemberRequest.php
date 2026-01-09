<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|unique:members,email,' . $this->member->id,
            'phone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|size:11|unique:members,cpf,' . $this->member->id,
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
            'zip_code' => 'nullable|string|size:8',
            'office_id' => 'nullable|exists:roles,id',
            'status' => 'sometimes|in:active,inactive',
            'roll_number' => 'nullable|integer|unique:members,roll_number,' . $this->member->id,
            'admission_date' => 'nullable|date',
            'admission_type' => 'nullable|string|max:100',
            'previous_church' => 'nullable|string|max:255',
            'dismissal_date' => 'nullable|date',
            'dismissal_type' => 'nullable|string|max:100',
            'destination_church' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/[^0-9]/', '', $this->cpf),
            ]);
        }

        if ($this->has('zip_code')) {
            $this->merge([
                'zip_code' => preg_replace('/[^0-9]/', '', $this->zip_code),
            ]);
        }
    }
}
