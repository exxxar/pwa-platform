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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();

            // 🔗 tenant
            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            // 👤 кто создал
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('tenant_users')
                ->nullOnDelete();

            // 🍽 официант
            $table->foreignId('officiant_id')
                ->nullable()
                ->constrained('tenant_users')
                ->nullOnDelete();

            // 🔢 номер стола
            $table->string('number')->index();

            // 🔒 закрыт
            $table->timestamp('closed_at')->nullable();

            // ➕ доп услуги
            $table->json('additional_services')->nullable();

            // ⚙️ конфиг
            $table->json('config')->nullable();

            // 📅 бронирование
            $table->date('booked_date_at')->nullable();
            $table->time('booked_time_at')->nullable();

            // 📝 инфа о брони
            $table->json('booked_info')->nullable();

            $table->timestamps();

            // ⚡ уникальность номера стола внутри tenant
            $table->unique(['tenant_id', 'number']);
        });

        Schema::create('table_tenant_user_clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('table_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tenant_user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 👥 роль клиента (опционально)
            $table->string('role')->nullable();
            // например: guest / vip / organizer

            $table->timestamps();

            // ❗ уникальность
            $table->unique(['table_id', 'tenant_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
        Schema::dropIfExists('table_tenant_user_clients');
    }
};
