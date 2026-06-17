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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_partner_id')->constrained("tenants")->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->integer('order_position')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('extra_charge')->default(0);
            $table->json('config')->nullable();
            $table->json('legal_info')->nullable();
            $table->timestamps();

            // индексы (на всякий)
            $table->index(['tenant_id', 'tenant_partner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
