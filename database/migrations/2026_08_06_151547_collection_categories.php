<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            // category_id может быть null, если это техническая группа (как 'sync_default_group')
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('category_name')->nullable();
            $table->string('selection_rule')->default('one'); // 'one' или 'all'
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_categories');
    }
};
