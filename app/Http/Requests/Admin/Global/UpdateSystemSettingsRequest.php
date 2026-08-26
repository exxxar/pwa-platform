<?php

namespace App\Http\Requests\Admin\Global;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billing' => 'nullable|array',
            'billing.default_plan' => 'nullable|string|max:50',
            'billing.trial_days' => 'nullable|integer|min:0',

            'notifications' => 'nullable|array',
            'notifications.email_enabled' => 'nullable|boolean',
            'notifications.telegram_enabled' => 'nullable|boolean',

            'limits' => 'nullable|array',
            'limits.max_users_per_tenant' => 'nullable|integer|min:1',
            'limits.max_products_per_tenant' => 'nullable|integer|min:1',
        ];
    }
}
