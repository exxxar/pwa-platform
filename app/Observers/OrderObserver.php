<?php

namespace App\Observers;

use App\Enums\OrderStatusEnum;
use App\Models\Tenant\Order;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * 🆕 ВРЕМЕННЫЙ ФЛАГ: Начислять бонусы сразу при создании заказа (без проверки статуса)
     * Установите в false, когда перейдете на нормальную логику (бонусы только после оплаты)
     */
    private const REWARD_ON_CREATE = false;

    /**
     * 🆕 Статусы, при которых начисляются реферальные бонусы
     */
    private const REWARDABLE_STATUSES = [
        OrderStatusEnum::Completed,      // 2 - Завершен
        OrderStatusEnum::InDelivery,     // 1 - В доставке (оплачен и отправлен)
    ];

    public function __construct(
        private ReferralService $referralService
    ) {}

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // ==========================================
        // 🎁 НАЧИСЛЕНИЕ РЕФЕРАЛЬНЫХ БОНУСОВ
        // ==========================================
        $shouldReward = self::REWARD_ON_CREATE
            || $this->isRewardableStatus($order->status);

        if ($shouldReward) {
            $this->processRewards($order);
        }

        // ==========================================
        // 💬 СОЗДАНИЕ ДИАЛОГА ПОДДЕРЖКИ ПО ЗАКАЗУ
        // ==========================================

        // Если диалог уже привязан (например, создан вручную), пропускаем
        if ($order->dialog_id) {
            return;
        }

        try {
            // 1. Создаем диалог
            $dialog = TenantDialog::create([
                'tenant_id' => $order->tenant_id,
                'tenant_user_id' => $order->tenant_user_id,
                'type' => 'order_support',
                'title' => 'Поддержка по заказу #' . $order->id,
                'is_closed' => false,
            ]);

            // 2. Привязываем ID диалога к заказу
            $order->dialog_id = $dialog->id;

            // 3. Сохраняем ТИХО (без повторного вызова событий), чтобы избежать бесконечного цикла
            $order->saveQuietly();

            // 4. Создаем приветственное системное сообщение
            TenantMessage::create([
                'dialog_id' => $dialog->id,
                'tenant_id' => $order->tenant_id,
                'tenant_user_id' => null, // null = сообщение от системы/бота
                'message' => "Здравствуйте! Это чат поддержки по вашему заказу #{$order->id}. Если у вас есть вопросы, напишите нам здесь.",
                'is_read' => true, // Системные сообщения считаем "прочитанными"
            ]);

            Log::info("💬 Создан диалог поддержки для заказа #{$order->id}", [
                'dialog_id' => $dialog->id,
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Ошибка создания диалога поддержки: " . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // ==========================================
        // 🎁 НАЧИСЛЕНИЕ БОНУСОВ ПРИ СМЕНЕ СТАТУСА
        // ==========================================

        // Если статус не менялся — ничего не делаем
        if (!$order->wasChanged('status')) {
            return;
        }

        // Если новый статус "вознаграждаемый" и бонусы еще не начислены
        if ($this->isRewardableStatus($order->status)) {
            $hasRewards = $order->referralRewards()->exists();

            if (!$hasRewards) {
                $this->processRewards($order);
            }
        }

        // ==========================================
        // 🚫 АВТОЗАКРЫТИЕ ДИАЛОГА ПРИ ОТМЕНЕ ЗАКАЗА
        // ==========================================

        $currentStatus = $this->toEnum($order->status);

        if ($currentStatus === OrderStatusEnum::Decline) {
            $this->closeDialog($order);
        }
    }

    // ==========================================
    // 🛠️ ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    /**
     * 🆕 Безопасное начисление бонусов с логированием
     */
    private function processRewards(Order $order): void
    {
        // Защита от двойного начисления
        if ($order->referralRewards()->exists()) {
            return;
        }

        try {
            $rewards = $this->referralService->processOrderRewards($order);

            if (!empty($rewards)) {
                Log::info("💰 Начислены реферальные бонусы для заказа #{$order->id}", [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'rewards_count' => count($rewards),
                    'rewards' => $rewards
                ]);
            }
        } catch (\Exception $e) {
            Log::error("❌ Ошибка начисления реферальных бонусов: " . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 🆕 Автозакрытие диалога при отмене/завершении заказа
     */
    private function closeDialog(Order $order): void
    {
        if (!$order->dialog_id) {
            return;
        }

        try {
            $dialog = TenantDialog::find($order->dialog_id);

            if ($dialog && !$dialog->is_closed) {
                $dialog->update(['is_closed' => true]);

                // Финальное системное сообщение
                TenantMessage::create([
                    'dialog_id' => $dialog->id,
                    'tenant_id' => $order->tenant_id,
                    'tenant_user_id' => null,
                    'message' => "Заказ #{$order->id} был отменен. Диалог автоматически закрыт.",
                    'is_read' => true,
                ]);

                Log::info("🔒 Диалог закрыт из-за отмены заказа #{$order->id}");
            }
        } catch (\Exception $e) {
            Log::error("❌ Ошибка закрытия диалога: " . $e->getMessage());
        }
    }

    /**
     * 🆕 Проверка, является ли статус "вознаграждаемым"
     */
    private function isRewardableStatus(int $status): bool
    {
        $statusEnum = $this->toEnum($status);

        if ($statusEnum === null) {
            return false;
        }

        return in_array($statusEnum, self::REWARDABLE_STATUSES, true);
    }

    /**
     * 🆕 Безопасная конвертация int в OrderStatusEnum
     */
    private function toEnum(int $status): ?OrderStatusEnum
    {
        return OrderStatusEnum::tryFrom($status);
    }
}
