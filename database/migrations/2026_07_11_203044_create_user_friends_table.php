<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('tenant_users')
                ->onDelete('cascade');

            $table->foreignId('friend_id')
                ->constrained('tenant_users')
                ->onDelete('cascade');

            // Статус заявки: pending, accepted, rejected
            $table->string('status', 20)->default('pending');

            // Кто отправил заявку
            $table->foreignId('initiator_id')
                ->constrained('tenant_users')
                ->onDelete('cascade');

            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            // Уникальная пара (в любом порядке)
            $table->unique(['user_id', 'friend_id']);

            $table->index(['tenant_id', 'status']);
            $table->index('friend_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_friends');
    }
};
