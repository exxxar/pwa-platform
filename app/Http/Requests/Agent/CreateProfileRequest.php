<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProfileRequest extends FormRequest
{
    /**
     * Определяем, авторизован ли пользователь
     */
    public function authorize(): bool
    {
        return true; // Пользователь уже авторизован (middleware auth:sanctum)
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'legal_type' => ['required', Rule::in(['self_employed', 'ip', 'legal_entity'])],

            // ИНН: 10 цифр для юр. лиц, 12 для физ. лиц/ИП
            'inn' => ['required', 'string', 'regex:/^(\d{10}|\d{12})$/'],

            // ОГРН/ОГРНИП: обязателен только если не самозанятый. 13 цифр для ИП, 15 для ООО
            'ogrn' => [
                'required_if:legal_type,ip,legal_entity',
                'nullable',
                'string',
                'regex:/^(\d{13}|\d{15})$/'
            ],

            // Расчетный счет: строго 20 цифр
            'bank_account' => ['required', 'string', 'size:20', 'regex:/^\d{20}$/'],

            // БИК: строго 9 цифр
            'bik' => ['required', 'string', 'size:9', 'regex:/^\d{9}$/'],

            // Название банка
            'bank_name' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    /**
     * Пользовательские сообщения об ошибках (опционально, но полезно для фронта)
     */
    public function messages(): array
    {
        return [
            'inn.regex' => 'ИНН должен содержать ровно 10 или 12 цифр.',
            'ogrn.regex' => 'ОГРН(ИП) должен содержать ровно 13 или 15 цифр.',
            'bank_account.size' => 'Расчетный счет должен содержать ровно 20 цифр.',
            'bank_account.regex' => 'Расчетный счет может содержать только цифры.',
            'bik.size' => 'БИК должен содержать ровно 9 цифр.',
            'bik.regex' => 'БИК может содержать только цифры.',
            'ogrn.required_if' => 'Для выбранного типа организации необходимо указать ОГРН(ИП).',
        ];
    }
}
