<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\Transaction;
use App\Models\Payout;
use App\DTOs\Agent\RequestPayoutDTO;
use App\Events\Agent\PayoutRequested;
use App\Exceptions\Agent\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentFinanceService
{
    /**
     * Запросить выплату
     */
    public function requestPayout(Agent $agent, RequestPayoutDTO $dto): Payout
    {
        return DB::transaction(function () use ($agent, $dto) {
            // Блокируем строку агента для избежания race condition
            $agent = Agent::where('id', $agent->id)->lockForUpdate()->first();

            if ($agent->balance < $dto->amount) {
                throw new InsufficientBalanceException(
                    "Недостаточно средств. Доступно: {$agent->balance} ₽"
                );
            }

            // Минимальная сумма выплаты (настраивается)
            if ($dto->amount < config('agents.min_payout', 1000)) {
                throw new \InvalidArgumentException(
                    "Минимальная сумма выплаты: " . config('agents.min_payout') . " ₽"
                );
            }

            // Снимаем с баланса, добавляем в pending
            $agent->decrement('balance', $dto->amount);
            $agent->increment('pending_balance', $dto->amount);

            // Создаём заявку на выплату со снимком реквизитов
            $payout = Payout::create([
                'agent_id'     => $agent->id,
                'amount'       => $dto->amount,
                'status'       => 'pending',
                'bank_details' => [
                    'bank_account' => $agent->bank_account,
                    'bik'          => $agent->bik,
                    'bank_name'    => $agent->bank_name,
                    'inn'          => $agent->inn,
                    'legal_type'   => $agent->legal_type,
                ],
                'comment' => $dto->comment,
            ]);

            // Запись в историю транзакций
            Transaction::create([
                'agent_id'  => $agent->id,
                'type'      => 'payout',
                'amount'    => $dto->amount,
                'title'     => "Заявка на вывод средств #{$payout->number}",
                'status'    => 'pending',
                'related_type' => Payout::class,
                'related_id'   => $payout->id,
            ]);

            event(new PayoutRequested($payout));

            Log::info("Agent #{$agent->id} requested payout #{$payout->number} for {$dto->amount} ₽");

            return $payout;
        });
    }

    /**
     * Начислить доход агенту (например, от оплаты клиентом)
     */
    public function creditIncome(Agent $agent, float $amount, string $title, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($agent, $amount, $title, $meta) {
            $agent = Agent::where('id', $agent->id)->lockForUpdate()->first();

            $agent->increment('balance', $amount);
            $agent->increment('total_earned', $amount);

            return Transaction::create([
                'agent_id'       => $agent->id,
                'type'           => 'income',
                'amount'         => $amount,
                'title'          => $title,
                'status'         => 'completed',
                'related_type'   => $meta['related_type'] ?? null,
                'related_id'     => $meta['related_id'] ?? null,
                'agent_client_id'=> $meta['agent_client_id'] ?? null,
            ]);
        });
    }

    /**
     * Обработать выплату (админом)
     */
    public function processPayout(Payout $payout, string $status, int $adminId, ?string $comment = null): Payout
    {
        return DB::transaction(function () use ($payout, $status, $adminId, $comment) {
            $agent = Agent::where('id', $payout->agent_id)->lockForUpdate()->first();

            $payout->update([
                'status'        => $status,
                'processed_at'  => now(),
                'processed_by'  => $adminId,
                'comment'       => $comment,
            ]);

            if ($status === 'rejected' || $status === 'cancelled') {
                // Возвращаем деньги на баланс
                $agent->decrement('pending_balance', $payout->amount);
                $agent->increment('balance', $payout->amount);

                Transaction::create([
                    'agent_id'     => $agent->id,
                    'type'         => 'refund',
                    'amount'       => $payout->amount,
                    'title'        => "Возврат отклонённой выплаты #{$payout->number}",
                    'status'       => 'completed',
                    'related_type' => Payout::class,
                    'related_id'   => $payout->id,
                ]);
            } elseif ($status === 'completed') {
                // Снимаем из pending
                $agent->decrement('pending_balance', $payout->amount);
            }

            return $payout->refresh();
        });
    }

    /**
     * Получить историю транзакций с пагинацией и фильтрами
     */
    public function getTransactions(Agent $agent, array $filters = [], int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Transaction::where('agent_id', $agent->id)
            ->orderByDesc('created_at');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Получить историю выплат
     */
    public function getPayouts(Agent $agent, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Payout::where('agent_id', $agent->id)->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}
