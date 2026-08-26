<?php

namespace App\Http\Requests\Admin\TenantData;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'sex' => 'nullable|string|in:male,female,other',
            'birthday' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_vip' => 'boolean',
            'meta' => 'nullable|array',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:tenant_roles,id',
        ];
    }
}
