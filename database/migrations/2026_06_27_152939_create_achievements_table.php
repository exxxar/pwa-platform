<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('icon', 100)->default('fa-solid fa-trophy');
            $table->string('condition_type', 50); // orders_count, reviews_count, etc.
            $table->integer('condition_value'); // 10, 50, 100
            $table->string('reward_type', 50)->nullable(); // cashback, discount, points
            $table->integer('reward_value')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'condition_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
