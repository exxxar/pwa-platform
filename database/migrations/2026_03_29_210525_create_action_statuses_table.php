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
        Schema::create('action_statuses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('tenant_user_id');

            $table->unsignedInteger('max_attempts')->default(1);
            $table->unsignedInteger('current_attempts')->default(0);

            $table->json('data')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Индексы для частых запросов
            $table->index('tenant_id');
            $table->index('tenant_user_id');
            $table->index(['tenant_id', 'tenant_user_id']);

            // Внешние ключи
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')         // судя по belongsTo(Bot::class)
                ->cascadeOnDelete();

            $table->foreign('tenant_user_id')
                ->references('id')
                ->on('tenant_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_statuses');
    }
};
