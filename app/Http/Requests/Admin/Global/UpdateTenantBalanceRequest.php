<?php

namespace App\Http\Requests\Admin\Global;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма обязательна для заполнения',
            'amount.numeric' => 'Сумма должна быть числом',
        ];
    }
}
