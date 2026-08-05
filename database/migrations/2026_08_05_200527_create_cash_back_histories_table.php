<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_back_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('tenant_user_id');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['credit', 'debit']); // credit = начисление, debit = списание
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('level')->default(1); // уровень реферальной системы
            $table->unsignedBigInteger('order_id')->nullable(); // связь с заказом
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'tenant_user_id']);
            $table->index('order_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_back_histories');
    }
};
