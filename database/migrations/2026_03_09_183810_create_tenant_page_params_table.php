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
        Schema::create('tenant_page_params', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_page_id')->constrained('tenant_pages')->cascadeOnDelete();

            $table->string('key');
            $table->string('type'); // string, number, boolean, image
            $table->text('value')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_page_params');
    }
};
