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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('agent_id')
                ->after('id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete()
                ->comment('Агент, если транзакция агентская');

            $table->foreignId('agent_client_id')
                ->after('agent_id')
                ->nullable()
                ->constrained('agent_clients')
                ->nullOnDelete()
                ->comment('Клиент агента, если применимо');

            // Morphs для связи с Invoice/Payout/etc.
            $table->nullableMorphs('related');

            $table->index(['agent_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['agent_client_id']);
            $table->dropMorphs('related');
            $table->dropColumn(['agent_id', 'agent_client_id']);
        });
    }
};
