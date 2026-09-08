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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();

            $table->string('number', 32)->unique()->comment('Номер заявки');
            $table->decimal('amount', 12, 2);

            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])
                ->default('pending')
                ->index();

            // Снимок реквизитов на момент заявки (чтобы изменения не влияли на старые заявки)
            $table->json('bank_details')->comment('Снимок банковских реквизитов');

            $table->text('comment')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Админ, обработавший заявку');

            $table->timestamps();

            $table->index(['agent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
