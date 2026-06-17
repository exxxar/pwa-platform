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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('title')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('image')->nullable();

            $table->text('description')->nullable();

            $table->json('config')->nullable();

            $table->string('link')->nullable();
            $table->string('link_type')->nullable();

            $table->timestamps();

            // FK (если используешь tenants таблицу)
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
