<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'memberId' => 'nullable|exists:members,id',
            'categoryId' => 'required|exists:categories,id',
            'costCenterId' => 'required|exists:cost_centers,id',
        ];
    }
}
