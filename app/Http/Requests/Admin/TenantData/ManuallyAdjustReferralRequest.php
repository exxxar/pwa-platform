<?php

namespace App\Http\Requests\Admin\TenantData;

use Illuminate\Foundation\Http\FormRequest;

class ManuallyAdjustReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => 'nullable|integer|min:1',
        ];
    }
}
