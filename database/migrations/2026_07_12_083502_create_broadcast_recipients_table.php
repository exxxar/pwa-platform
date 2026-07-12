<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('broadcasts')->onDelete('cascade');
            $table->foreignId('tenant_user_id')->constrained('tenant_users')->onDelete('cascade');

            // Статус: pending, sent, delivered, read, failed
            $table->string('status', 20)->default('pending');

            // ID сообщения в диалоге
            $table->unsignedBigInteger('dialog_message_id')->nullable();

            // Ошибки
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->unique(['broadcast_id', 'tenant_user_id']);
            $table->index(['broadcast_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_recipients');
    }
};
