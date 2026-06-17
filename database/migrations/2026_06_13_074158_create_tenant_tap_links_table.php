<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_tap_links', function (Blueprint $table) {
            $table->id();

            // Связь с тенантом (каскадное удаление: если тенант удален, ссылки тоже удалятся)
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('title'); // Заголовок кнопки (напр. "Наш Telegram")
            $table->string('url');   // Ссылка (напр. "https://t.me/...")

            // Иконка (класс FontAwesome, напр. "fa-brands fa-telegram")
            $table->string('icon')->nullable()->default('fa-solid fa-link');

            // Цвет фона иконки (напр. "#0088cc")
            $table->string('icon_bg')->nullable()->default('#6c757d');

            $table->integer('sort_order')->default(0); // Для сортировки
            $table->boolean('is_active')->default(true); // Включить/выключить ссылку

            $table->timestamps();

            // Индекс для быстрой выборки активных ссылок конкретного тенанта
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_tap_links');
    }
};
