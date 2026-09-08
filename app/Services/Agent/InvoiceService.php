<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\Invoice;
use App\Models\AgentClient;
use App\DTOs\Agent\CreateInvoiceDTO;
use App\Events\Agent\InvoiceCreated;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Создать счёт для клиента
     */
    public function createInvoice(Agent $agent, CreateInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($agent, $dto) {
            // Если передан email — ищем/создаём клиента
            $client = null;
            if ($dto->tenant_user_id) {
                $client = AgentClient::firstOrCreate(
                    ['agent_id' => $agent->id, 'tenant_user_id' => $dto->tenant_user_id],
                    ['status' => 'active']
                );
            }

            $invoice = Invoice::create([
                'agent_id'        => $agent->id,
                'agent_client_id' => $client?->id,
                'client_name'     => $dto->client_name,
                'client_email'    => $dto->client_email,
                'client_phone'    => $dto->client_phone,
                'service_type'    => $dto->service_type,
                'amount'          => $dto->amount,
                'description'     => $dto->description,
                'status'          => 'draft',
                'due_date'        => now()->addDays(14),
            ]);

            event(new InvoiceCreated($invoice));

            return $invoice;
        });
    }

    /**
     * Отправить счёт клиенту (по email)
     */
    public function sendInvoice(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \InvalidArgumentException('Счёт уже отправлен или оплачен');
        }

        $invoice->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        // TODO: Отправить email через Mail
        // Mail::to($invoice->client_email)->queue(new InvoiceMail($invoice));

        return $invoice;
    }

    /**
     * Отметить счёт как оплаченный (вызывается при получении платежа)
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            // Начисляем доход агенту
            app(AgentFinanceService::class)->creditIncome(
                $invoice->agent,
                $invoice->amount,
                "Оплата по счёту #{$invoice->number}",
                [
                    'related_type'    => Invoice::class,
                    'related_id'      => $invoice->id,
                    'agent_client_id' => $invoice->agent_client_id,
                ]
            );

            return $invoice;
        });
    }

    /**
     * Список счетов агента
     */
    public function getAgentInvoices(Agent $agent, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Invoice::where('agent_id', $agent->id)->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}
