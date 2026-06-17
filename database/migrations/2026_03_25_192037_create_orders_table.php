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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('tenant_user_id')->nullable()->index();
            $table->unsignedBigInteger('table_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();

            $table->json('delivery_service_info')->nullable();
            $table->json('deliveryman_info')->nullable();
            $table->json('product_details')->nullable();

            $table->integer('product_count')->nullable();

            $table->double('summary_price')->default(0);
            $table->double('delivery_price')->nullable();
            $table->double('delivery_range')->nullable();

            $table->double('deliveryman_latitude')->nullable();
            $table->double('deliveryman_longitude')->nullable();

            $table->integer('service_rating')->nullable();
            $table->text('service_review')->nullable();

            $table->text('delivery_note')->nullable();

            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();

            $table->integer('status')->default(0);
            $table->string('order_type')->nullable();

            $table->boolean('is_cashback_crediting')->default(false);

            $table->timestamp('payed_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('tenant_user_id')
                ->references('id')
                ->on('tenant_users')
                ->nullOnDelete();

            $table->foreign('table_id')
                ->references('id')
                ->on('tables')
                ->nullOnDelete();

            $table->foreign('location_id')
                ->references('id')
                ->on('tenant_user_addresses')
                ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
