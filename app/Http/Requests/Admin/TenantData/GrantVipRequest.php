<?php

namespace App\Http\Requests\Admin\TenantData;

use Illuminate\Foundation\Http\FormRequest;

class GrantVipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => 'nullable|integer|min:1|max:3650',
        ];
    }

    public function messages(): array
    {
        return [
            'days.min' => 'Количество дней должно быть больше 0',
            'days.max' => 'Максимальный срок VIP - 3650 дней (10 лет)',
        ];
    }
}
