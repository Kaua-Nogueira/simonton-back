<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'date' => 'required|date|before_or_equal:today',
            'payment_method' => 'nullable|in:pix,boleto,ted,cartao,dinheiro,outros',
            'member_id' => 'nullable|exists:members,id',
            'category_id' => 'nullable|exists:categories,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Tipo da transação é obrigatório',
            'type.in' => 'Tipo deve ser entrada (income) ou saída (expense)',
            'amount.required' => 'Valor é obrigatório',
            'amount.min' => 'Valor deve ser maior que zero',
            'date.required' => 'Data é obrigatória',
            'date.before_or_equal' => 'Data não pode ser futura',
            'payment_method.in' => 'Método de pagamento inválido',
        ];
    }
}
