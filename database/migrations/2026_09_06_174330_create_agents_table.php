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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_user_id')
                ->unique()
                ->constrained('tenant_users')
                ->cascadeOnDelete()
                ->comment('Рабочий профиль агента (ссылка на TenantUser)');

            $table->enum('status', ['pending', 'verified', 'suspended'])
                ->default('pending')
                ->index();

            $table->enum('verification_status', ['not_started', 'partial', 'verified', 'rejected'])
                ->default('not_started');

            // Финансы
            $table->decimal('balance', 12, 2)->default(0)->comment('Доступно к выводу');
            $table->decimal('pending_balance', 12, 2)->default(0)->comment('Ожидает подтверждения');
            $table->decimal('total_earned', 12, 2)->default(0)->comment('Всего заработано за всё время');

            // Юр. статус
            $table->enum('legal_type', ['self_employed', 'ip', 'legal_entity'])
                ->default('self_employed');
            $table->string('inn', 20)->nullable();
            $table->string('ogrn', 20)->nullable();

            // Банковские реквизиты
            $table->string('bank_account', 30)->nullable();
            $table->string('bik', 15)->nullable();
            $table->string('bank_name')->nullable();

            // Реферальная программа агента
            $table->string('referral_code', 32)->unique()->comment('Уникальный код для реферальной ссылки');
            $table->unsignedInteger('referrals_count')->default(0)->comment('Количество привлечённых рефералов');
            $table->unsignedInteger('clients_count')->default(0)->comment('Количество клиентов');
            $table->unsignedInteger('tenant_count')->default(0)->comment('Количество созданных приложений');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
