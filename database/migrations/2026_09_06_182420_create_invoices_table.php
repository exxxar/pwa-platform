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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('agent_client_id')
                ->nullable()
                ->constrained('agent_clients')
                ->nullOnDelete();

            $table->string('number', 32)->unique()->comment('Номер счёта');
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_phone', 20)->nullable();

            $table->enum('service_type', ['bot', 'setup', 'support', 'subscription', 'other'])
                ->default('bot');

            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();

            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled', 'overdue'])
                ->default('draft')
                ->index();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
