<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_user_id')->constrained('tenant_users')->onDelete('cascade');
            $table->string('stat_key', 100); // orders_count, reviews_count, etc.
            $table->integer('stat_value')->default(0);
            $table->timestamps();

            $table->unique(['tenant_user_id', 'stat_key']);
            $table->index('stat_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
