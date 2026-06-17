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
        Schema::create('baskets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('count')->default(1);
            $table->longText('comment')->nullable();

            $table->foreignId('tenant_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->timestamp('ordered_at')->nullable();

            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();

            $table->json('params')->nullable();

            $table->foreignId('table_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('table_approved_at')->nullable();

            $table->foreignId('tenant_partner_id')->nullable()->constrained("partners")->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baskets');
    }
};
