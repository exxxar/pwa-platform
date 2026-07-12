<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('tenant_user_id')->constrained('tenant_users')->onDelete('cascade');

            $table->string('title', 255);
            $table->text('message')->nullable();

            // Статус: draft, scheduled, sending, sent, failed, cancelled
            $table->string('status', 20)->default('draft');

            // Тип получателей: all, active, vip, segment, custom
            $table->string('recipient_type', 20)->default('all');
            $table->json('recipient_filters')->nullable();

            // Запланированное время
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Статистика
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
