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
        Schema::create('agent_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();

            $table->enum('type', [
                'passport',
                'snils',
                'registration_cert',   // Справка о постановке на учёт (для самозанятых)
                'ogrn_cert',           // Свидетельство ОГРНИП/ОГРН
                'tax_cert',            // Налоговое свидетельство
                'bank_card',           // Карточка предприятия с реквизитами
                'other'
            ])->index();

            $table->string('title');
            $table->string('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('file_size')->nullable(); // в байтах

            $table->boolean('is_required')->default(true);
            $table->boolean('is_uploaded')->default(false);
            $table->timestamp('uploaded_at')->nullable();

            $table->enum('verification_status', ['pending', 'approved', 'rejected'])
                ->default('pending');
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['agent_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_documents');
    }
};
