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
        Schema::create('agent_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();
            $table->foreignId('tenant_user_id')
                ->constrained('tenant_users')
                ->cascadeOnDelete()
                ->comment('Клиент (TenantUser, который является клиентом этого агента)');

            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Один TenantUser может быть клиентом у одного агента только один раз
            $table->unique(['agent_id', 'tenant_user_id']);
            $table->index('tenant_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_clients');
    }
};
