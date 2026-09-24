<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('short_text', 255)->nullable();
            $table->text('full_text')->nullable();
            $table->string('image')->nullable();
            $table->string('badge')->nullable();
            $table->string('button_text')->nullable();
            $table->enum('action_type', ['url', 'partner', 'none'])->default('none');
            $table->string('action_value')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
