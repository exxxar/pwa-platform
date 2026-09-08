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
        Schema::table('ingredient_groups', function (Blueprint $table) {
            // Правило выбора: single, multiple, all, optional
            $table->string('selection_rule')->default('multiple')->after('name');

            // Минимальное количество для выбора (0 = не ограничено снизу, если не is_required)
            $table->unsignedInteger('min_select')->default(0)->after('selection_rule');

            // Максимальное количество для выбора (0 = не ограничено сверху)
            $table->unsignedInteger('max_select')->default(0)->after('min_select');

            // Обязательна ли группа для выбора хотя бы min_select элементов
            $table->boolean('is_required')->default(false)->after('max_select');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredient_groups', function (Blueprint $table) {
            $table->dropColumn([
                'selection_rule',
                'min_select',
                'max_select',
                'is_required'
            ]);
        });
    }
};
