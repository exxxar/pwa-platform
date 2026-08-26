<?php

namespace App\Services\Tenants;

use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public static function call(): self
    {
        return app(self::class);
    }

    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    /**
     * Создает новую запись транзакции в статусе "ожидание"
     */
    public function createPending(
        int $tenantId,
        int $tenantUserId,
        ?int $orderId,
        string $externalPaymentId,
        float $amount,
        array $metaData = [],
        string $provider = 'tinkoff'
    ): Transaction {
        return Transaction::create([
            'tenant_id' => $tenantId,
            'tenant_user_id' => $tenantUserId,
            'order_id' => $orderId,
            'provider' => $provider,
            'external_payment_id' => $externalPaymentId,
            'amount' => $amount,
            'currency' => 'RUB',
            'status' => 'pending',
            'meta' => $metaData,
        ]);
    }

    /**
     * Обрабатывает успешный вебхук от платежной системы
     */
    public function markAsSuccessful(string $externalPaymentId, int $tenantId, array $webhookData = []): ?Transaction
    {
        $transaction = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->where('external_payment_id', $externalPaymentId)
            ->first();

        if (!$transaction) {
            Log::warning("[TransactionService] Транзакция с external_payment_id=$externalPaymentId не найдена для tenant_id=$tenantId");
            return null;
        }

        if ($transaction->status === 'success') {
            return $transaction; // Уже обработано (защита от повторных вебхуков)
        }

        // Обновляем статус и сохраняем сырые данные вебхука в meta
        $meta = array_merge($transaction->meta ?? [], [
            'webhook_response' => $webhookData,
            'confirmed_at' => now()->toIso8601String(),
        ]);

        $transaction->update([
            'status' => 'success',
            'paid_at' => now(),
            'meta' => $meta,
        ]);

        Log::info("[TransactionService] Транзакция #{$transaction->id} успешно помечена как оплаченная.");

        return $transaction;
    }

    /**
     * Обрабатывает отказ или возврат платежа
     */
    public function markAsFailed(string $externalPaymentId, int $tenantId, string $reason = 'Rejected'): ?Transaction
    {
        $transaction = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->where('external_payment_id', $externalPaymentId)
            ->whereIn('status', ['pending', 'success']) // Можно отменить или вернуть
            ->first();

        if (!$transaction) {
            return null;
        }

        $meta = array_merge($transaction->meta ?? [], [
            'fail_reason' => $reason,
            'failed_at' => now()->toIso8601String(),
        ]);

        $transaction->update([
            'status' => $reason === 'Refunded' ? 'refunded' : 'failed',
            'meta' => $meta,
        ]);

        return $transaction;
    }

    /**
     * Получает историю транзакций пользователя
     */
    public function getUserTransactions(TenantUser $user, int $limit = 10)
    {
        return Transaction::query()
            ->where('tenant_user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Находит транзакцию по внешнему ID
     */
    public function findByExternalId(string $externalPaymentId, int $tenantId): ?Transaction
    {
        return Transaction::query()
            ->where('tenant_id', $tenantId)
            ->where('external_payment_id', $externalPaymentId)
            ->first();
    }
}
