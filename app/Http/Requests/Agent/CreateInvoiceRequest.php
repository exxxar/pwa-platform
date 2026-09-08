<?php
// app/Http/Requests/Agent/CreateInvoiceRequest.php
namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name'    => ['required', 'string', 'max:255'],
            'client_email'   => ['nullable', 'email', 'max:255'],
            'client_phone'   => ['nullable', 'string', 'max:20'],
            'service_type'   => ['required', 'in:bot,setup,support,subscription,other'],
            'amount'         => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'tenant_user_id' => ['nullable', 'integer', 'exists:tenant_users,id'],
        ];
    }
}
