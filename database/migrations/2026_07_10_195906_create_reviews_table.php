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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Тенантная привязка
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('tenant_user_id')->constrained('tenant_users')->onDelete('cascade');

            // Связи (опциональные)
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');

            // Данные отзыва
            $table->tinyInteger('rating')->default(5)->comment('Оценка от 1 до 5');
            $table->text('text')->nullable()->comment('Текст отзыва');
            $table->string('title')->nullable()->comment('Заголовок отзыва');

            // Статус модерации
            $table->tinyInteger('status')->default(0)->comment('0=на модерации, 1=одобрен, 2=отклонен');

            // Метаданные
            $table->json('images')->nullable()->comment('Фото отзыва');
            $table->integer('likes_count')->default(0);
            $table->integer('dislikes_count')->default(0);

            // Ответ администратора
            $table->text('admin_response')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['tenant_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index(['tenant_user_id']);
            $table->index(['rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
