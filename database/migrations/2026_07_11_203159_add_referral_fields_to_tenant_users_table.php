<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            // Уникальный реферальный код пользователя
            $table->string('referral_code', 20)
                ->unique()
                ->nullable()
                ->after('id');

            // Кто пригласил этого пользователя (прямой реферер)
            $table->foreignId('referred_by')
                ->nullable()
                ->after('referral_code')
                ->constrained('tenant_users')
                ->onDelete('set null');

            // Счётчики для быстрого доступа
            $table->unsignedInteger('referrals_count')->default(0);
            $table->unsignedInteger('friends_count')->default(0);
            $table->decimal('total_referral_earnings', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'referral_code',
                'referred_by',
                'referrals_count',
                'friends_count',
                'total_referral_earnings',
            ]);
        });
    }
};
