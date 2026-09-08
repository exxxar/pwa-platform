<?php
// app/Http/Requests/Agent/UploadDocumentRequest.php
namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', 'between:1,10'],
            'file'             => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10 МБ
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Допустимые форматы: PDF, JPG, PNG',
            'file.max'   => 'Максимальный размер файла: 10 МБ',
        ];
    }
}
