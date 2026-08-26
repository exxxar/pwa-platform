<?php

namespace App\Http\Requests\Admin\TenantData;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Статус заказа обязателен',
        ];
    }
}
