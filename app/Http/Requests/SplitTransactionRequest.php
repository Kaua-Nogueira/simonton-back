<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SplitTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'splits' => 'required|array|min:2',
            'splits.*.categoryId' => 'required|exists:categories,id',
            'splits.*.costCenterId' => 'nullable|exists:cost_centers,id',
            'splits.*.amount' => 'required|numeric|min:0.01',
            'splits.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'splits.required' => 'Divisões são obrigatórias',
            'splits.min' => 'Deve haver pelo menos 2 divisões',
            'splits.*.categoryId.required' => 'Categoria é obrigatória para cada divisão',
            'splits.*.amount.required' => 'Valor é obrigatório para cada divisão',
            'splits.*.amount.min' => 'Valor deve ser maior que zero',
        ];
    }
}
