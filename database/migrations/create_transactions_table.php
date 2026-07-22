<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy связи
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('tenant_user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();

            // Данные платежа
            $table->string('provider')->default('tinkoff'); // tinkoff, yookassa, etc.
            $table->string('external_payment_id')->nullable()->index(); // PaymentId от Т-Банка
            $table->decimal('amount', 10, 2)->default(0); // Сумма в рублях
            $table->string('currency', 3)->default('RUB');

            // Статус: pending, success, failed, refunded
            $table->string('status')->default('pending')->index();

            // Дополнительные данные (чек, детали товаров, ответ вебхука)
            $table->json('meta')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Внешние ключи
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('tenant_user_id')->references('id')->on('tenant_users')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();

            // Индекс для быстрого поиска по связке тенант + внешний ID (важно для вебхуков)
            $table->index(['tenant_id', 'external_payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
