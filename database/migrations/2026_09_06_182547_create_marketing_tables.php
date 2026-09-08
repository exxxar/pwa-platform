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
        Schema::create('marketing_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();       // FontAwesome класс
            $table->string('color')->nullable();      // CSS gradient
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('marketing_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('marketing_categories')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->unsignedInteger('file_size')->nullable(); // байты
            $table->string('file_type', 20)->nullable();      // pdf, doc, png, etc.
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_materials');
        Schema::dropIfExists('marketing_categories');
    }
};
