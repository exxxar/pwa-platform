<?php
// app/Http/Requests/Agent/AddClientRequest.php
namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class AddClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_user_id' => ['required', 'integer', 'exists:tenant_users,id'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}
