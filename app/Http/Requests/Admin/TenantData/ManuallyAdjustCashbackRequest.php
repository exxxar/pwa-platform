<?php

namespace App\Http\Requests\Admin\TenantData;

use Illuminate\Foundation\Http\FormRequest;

class ManuallyAdjustCashbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_user_id' => 'required|exists:tenant_users,id',
            'amount' => 'required|numeric',
            'type' => 'required|in:credit,debit',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_user_id.required' => 'ID пользователя обязателен',
            'amount.required' => 'Сумма обязательна',
            'type.required' => 'Тип операции обязателен',
            'type.in' => 'Тип должен быть credit или debit',
        ];
    }
}
