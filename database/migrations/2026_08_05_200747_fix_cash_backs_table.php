<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_backs', function (Blueprint $table) {
            // Удаляем ненужные поля, если они есть
            if (Schema::hasColumn('cash_backs', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('cash_backs', 'bot_id')) {
                $table->dropColumn('bot_id');
            }
            if (Schema::hasColumn('cash_backs', 'bot_user_id')) {
                $table->dropColumn('bot_user_id');
            }

            // Добавляем уникальное ограничение
            $table->unique(['tenant_id', 'tenant_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_backs', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'tenant_user_id']);
        });
    }
};
