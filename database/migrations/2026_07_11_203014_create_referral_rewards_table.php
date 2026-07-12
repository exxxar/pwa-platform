<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Кто получил награду
            $table->foreignId('user_id')
                ->constrained('tenant_users')
                ->onDelete('cascade');

            // От какого заказа/действия
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null');

            // Реферал, чьё действие принесло бонус
            $table->foreignId('from_referral_id')
                ->nullable()
                ->constrained('tenant_users')
                ->onDelete('set null');

            // Уровень (1, 2, 3)
            $table->tinyInteger('level');

            // Тип награды: cashback, bonus, discount
            $table->string('type', 30)->default('cashback');

            // Сумма
            $table->decimal('amount', 10, 2)->default(0);

            // Процент от заказа
            $table->decimal('percent', 5, 2)->default(0);

            // Описание
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['user_id', 'level']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};
