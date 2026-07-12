<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('broadcasts')->onDelete('cascade');

            // Тип: image, video, audio
            $table->string('type', 20);
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            // Для изображений
            $table->string('caption')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['broadcast_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_media');
    }
};
