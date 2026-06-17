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
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();

            $table->date('birthday')->nullable();

            $table->enum('sex', ['male', 'female'])->default('male');

            $table->json('meta')->nullable(); // любые доп. поля

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('tenant_users')
                ->nullOnDelete();


            $table->timestamp('blocked_at')->nullable();
            $table->string('blocked_message')->nullable();
            // индексы
            $table->index('tenant_id');
            $table->index('parent_id');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
