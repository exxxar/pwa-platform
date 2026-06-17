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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('theme_color')->default('#ffffff');
            $table->string('app_type')->default('shop');

            $table->string('order_channel', 255)->nullable();
            $table->double('balance')->default(0);
            $table->double('tax_per_day')->default(0);
            $table->json('meta')->nullable();

            $table->boolean('is_active')->default(false);

            $table->longText('welcome_message')->nullable();
            $table->longText('maintenance_message')->nullable();
            $table->longText('blocked_message')->nullable();
            $table->longText('long_description')->nullable();
            $table->longText('short_description')->nullable();
            $table->integer('cashback_fire_percent')->default(0);
            $table->integer('cashback_fire_period')->default(0);


            $table->string('vk_shop_link')->nullable();

            $table->double('level_1')->nullable();
            $table->double('level_2')->nullable();
            $table->double('level_3')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
