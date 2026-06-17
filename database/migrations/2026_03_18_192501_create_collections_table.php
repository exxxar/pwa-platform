<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true);

            $table->double('discount')->nullable();
            $table->integer('order_position')->nullable();

            $table->json('config')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });

        // Pivot table for many-to-many with products
        Schema::create('collection_product', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('order_position')->nullable();

            $table->primary(['collection_id', 'product_id']);

            $table->foreign('collection_id')
                ->references('id')
                ->on('collections')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
    }
};
