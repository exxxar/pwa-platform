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
        Schema::create('agent_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();

            $table->string('referral_code', 32)->index()
                ->comment('Код, по которому пришёл реферал');

            $table->foreignId('referred_tenant_user_id')
                ->nullable()
                ->constrained('tenant_users')
                ->nullOnDelete()
                ->comment('Кто пришёл (если зарегистрировался)');

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            $table->boolean('converted')->default(false)
                ->comment('Стал ли реферал клиентом/агентом');
            $table->timestamp('converted_at')->nullable();
            $table->decimal('bonus_amount', 12, 2)->default(0)
                ->comment('Бонус агенту за конверсию');

            $table->timestamps();

            $table->index(['agent_id', 'converted']);
            $table->index('referred_tenant_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_referrals');
    }
};
