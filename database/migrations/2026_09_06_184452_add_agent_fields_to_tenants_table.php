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
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('agent_id')
                ->after('id') // или после нужного поля
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete()
                ->comment('Агент, который создал/обслуживает приложение');

            $table->foreignId('client_id')
                ->after('agent_id')
                ->nullable()
                ->constrained('agent_clients')
                ->nullOnDelete()
                ->comment('Клиент, для которого создано приложение');

            $table->index(['agent_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['client_id']);
            $table->dropIndex(['agent_id', 'client_id']);
            $table->dropColumn(['agent_id', 'client_id']);
        });
    }
};
