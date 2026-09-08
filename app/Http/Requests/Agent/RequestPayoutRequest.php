<?php
// app/Http/Requests/Agent/RequestPayoutRequest.php
namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class RequestPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'  => ['required', 'numeric', 'min:' . config('agents.min_payout', 1000)],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Минимальная сумма выплаты: ' . config('agents.min_payout', 1000) . ' ₽',
        ];
    }
}
