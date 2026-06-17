<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();

            // 🔗 связь с арендатором
            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            // 📦 тип интеграции
            $table->string('type');
            // amo, bitrix, iiko, yclients, frontpad, cdek
            // 🏷 имя (чтобы можно было несколько подключений)
            $table->string('name')->nullable();
            // 🔐 авторизация
            $table->json('credentials')->nullable();
            /*
                {
                    "token": "...",
                    "refresh_token": "...",
                    "domain": "...",
                    "api_key": "..."
                }
            */
            // ⚙️ настройки
            $table->json('settings')->nullable();
            /*
                {
                    "sync_orders": true,
                    "sync_clients": true
                }
            */

            // 📊 статус
            $table->boolean('is_active')->default(true);

            // ❌ ошибка последней синхронизации
            $table->text('last_error')->nullable();

            // 🕓 последний sync
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // 🔍 индексы
            $table->index(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
