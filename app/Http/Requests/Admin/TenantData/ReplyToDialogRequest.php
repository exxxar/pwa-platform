<?php

namespace App\Http\Requests\Admin\TenantData;

use Illuminate\Foundation\Http\FormRequest;

class ReplyToDialogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:5000',
            'meta' => 'nullable|array',
            'meta.attachment' => 'nullable|array',
            'meta.attachment.path' => 'required_with:meta.attachment|string|max:255',
            'meta.attachment.size' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Сообщение обязательно для отправки',
        ];
    }
}
