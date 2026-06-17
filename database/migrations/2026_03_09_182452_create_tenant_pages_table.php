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
        Schema::create('tenant_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('slug');      // /home, /catalog, /product/{id}
            $table->string('title');
            $table->boolean('is_system')->default(false); // системная или созданная пользователем

            $table->json('structure')->nullable(); // JSON из конструктора
            $table->json('settings')->nullable();  // SEO, meta, layout

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_pages');
    }
};
