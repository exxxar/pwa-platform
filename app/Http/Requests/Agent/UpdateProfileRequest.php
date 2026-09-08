<?php
// app/Http/Requests/Agent/UpdateProfileRequest.php
namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:255'],
            'phone'        => ['sometimes', 'string', 'max:20'],
            'email'        => ['sometimes', 'email', 'max:255'],
            'legal_type'   => ['sometimes', 'in:self_employed,ip,legal_entity'],
            'inn'          => ['sometimes', 'string', 'regex:/^\d{10}$|^\d{12}$/'],
            'ogrn'         => ['nullable', 'string', 'regex:/^\d{13}$|^\d{15}$/'],
            'bank_account' => ['nullable', 'string', 'regex:/^\d{20}$/'],
            'bik'          => ['nullable', 'string', 'regex:/^\d{9}$/'],
            'bank_name'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'inn.regex'     => 'ИНН должен содержать 10 цифр (ИП/ООО) или 12 цифр (самозанятый)',
            'ogrn.regex'    => 'ОГРН/ОГРНИП должен содержать 13 или 15 цифр',
            'bank_account.regex' => 'Расчётный счёт должен содержать 20 цифр',
            'bik.regex'     => 'БИК должен содержать 9 цифр',
        ];
    }
}
