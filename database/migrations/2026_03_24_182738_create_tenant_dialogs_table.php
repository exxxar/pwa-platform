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
        Schema::create('tenant_dialogs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_user_id')->constrained()->cascadeOnDelete();

            $table->string('type')->default('support');
            // support | order | system

            $table->string('title')->nullable();
            $table->string('external_task_id')->nullable();

            $table->boolean('is_closed')->default(false);

            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'tenant_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_dialogs');
    }
};
