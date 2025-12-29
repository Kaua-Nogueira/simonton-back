<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email',
            'phone' => 'required|string|max:20',
            'cpf' => 'required|string|size:11|unique:members,cpf',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
            'zip_code' => 'nullable|string|size:8',
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.required' => 'CPF é obrigatório',
            'cpf.size' => 'CPF deve conter 11 dígitos',
            'cpf.unique' => 'CPF já cadastrado',
            'email.unique' => 'Email já cadastrado',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Remove formatting from CPF
        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/[^0-9]/', '', $this->cpf),
            ]);
        }

        // Remove formatting from zip_code
        if ($this->has('zip_code')) {
            $this->merge([
                'zip_code' => preg_replace('/[^0-9]/', '', $this->zip_code),
            ]);
        }
    }
}
