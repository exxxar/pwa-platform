<?php

namespace App\Http\Requests\Admin\Global;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tenants')->ignore($this->route('tenant')),
            ],
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
}
