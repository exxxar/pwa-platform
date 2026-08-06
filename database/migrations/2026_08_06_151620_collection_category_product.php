<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_category_id')->constrained('collection_categories')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Запрещаем дублирование одного и того же товара в одной и той же группе категории
           // $table->unique(['collection_category_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_category_product');
    }
};
