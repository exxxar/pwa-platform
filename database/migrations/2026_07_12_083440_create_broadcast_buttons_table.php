<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_buttons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('broadcasts')->onDelete('cascade');

            $table->string('text', 100);
            $table->string('url')->nullable(); // для URL кнопок
            $table->string('callback_data')->nullable(); // для callback кнопок
            $table->string('type', 20)->default('callback'); // callback, url

            $table->integer('row_index')->default(0); // номер строки
            $table->integer('position')->default(0); // позиция в строке

            $table->timestamps();

            $table->index(['broadcast_id', 'row_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_buttons');
    }
};
