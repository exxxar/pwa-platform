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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants');;
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->json('images')->nullable();
            $table->json('config')->nullable();
            $table->json('dimensions')->nullable();
            $table->string('external_source')->nullable();
            $table->string('external_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('not_for_delivery')->default(false);
            $table->boolean('in_stop_list')->default(false);
            $table->boolean('is_weight_product')->default(false);
            $table->integer('order_position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_id', 'category_id']);
            $table->index('category_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
