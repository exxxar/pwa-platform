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
        Schema::create('tenant_user_addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title')->nullable(); // "Дом", "Работа"

            $table->string('city')->nullable();
            $table->string('country')->nullable();

            $table->string('address'); // текстовый адрес

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->boolean('is_default')->default(false);

            $table->json('meta')->nullable(); // подъезд, этаж и т.д.

            $table->timestamps();

            // индексы
            $table->index('tenant_user_id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user_addresses');
    }
};
