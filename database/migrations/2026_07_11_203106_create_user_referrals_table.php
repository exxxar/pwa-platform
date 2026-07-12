<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Кто пригласил
            $table->foreignId('referrer_id')
                ->constrained('tenant_users')
                ->onDelete('cascade');

            // Кого пригласили
            $table->foreignId('referred_id')
                ->constrained('tenant_users')
                ->onDelete('cascade');

            // Уровень реферальства (1, 2, 3)
            $table->tinyInteger('level')->default(1);

            // Статус
            $table->boolean('is_active')->default(true);

            // Дата регистрации реферала
            $table->timestamp('registered_at')->nullable();

            $table->timestamps();

            // Один пользователь может быть рефералом только один раз
            $table->unique(['referrer_id', 'referred_id']);

            // Индексы для быстрых запросов
            $table->index(['tenant_id', 'level']);
            $table->index(['referrer_id', 'level']);
            $table->index('referred_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_referrals');
    }
};
