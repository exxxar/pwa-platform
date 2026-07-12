<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            // Статус пользователя (активен/заблокирован)
            $table->boolean('is_active')
                ->default(true)
                ->after('email');

            // VIP статус
            $table->boolean('is_vip')
                ->default(false)
                ->after('is_active');

            // Дата активации VIP
            $table->timestamp('vip_activated_at')
                ->nullable()
                ->after('is_vip');

            // Дата окончания VIP (если временный)
            $table->timestamp('vip_expires_at')
                ->nullable()
                ->after('vip_activated_at');

            // Индексы для производительности
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_vip']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropIndex(['tenant_id', 'is_vip']);
            $table->dropColumn([
                'is_active',
                'is_vip',
                'vip_activated_at',
                'vip_expires_at',
            ]);
        });
    }
};
