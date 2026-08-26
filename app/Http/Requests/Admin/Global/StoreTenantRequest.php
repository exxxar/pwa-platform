<?php

namespace App\Http\Requests\Admin\Global;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Проверка прав будет в Policy
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tenants,slug',
            'short_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'theme_color' => 'nullable|string|max:20',
            'background_color' => 'nullable|string|max:20',
            'app_type' => 'nullable|string|max:50',
            'plan_slug' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'meta' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название тенанта обязательно для заполнения',
            'slug.unique' => 'Такой slug уже существует',
        ];
    }
}
