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
        Schema::table('orders', function (Blueprint $table) {
            // Добавляем поле после tenant_user_id для логической группировки
            $table->foreignId('dialog_id')
                ->nullable()
                ->after('tenant_user_id')
                ->constrained('tenant_dialogs')
                ->nullOnDelete(); // Если диалог удалят, в заказе останется null, а не ошибка

            // Индекс для ускорения поиска диалога по заказу
            $table->index('dialog_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dialog_id']);
            $table->dropColumn('dialog_id');
        });
    }
};
