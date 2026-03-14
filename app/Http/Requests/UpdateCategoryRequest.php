<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'code' => 'nullable|string|max:20|unique:categories,code,' . $this->category->id,
            'is_active' => 'boolean',
        ];
    }
}
