<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {

            $table->string('external_id')->nullable()->index()->after('id');
            // Добавляем новые поля
            $table->string('type')->default('manual')->after('description');
            $table->string('pricing_type')->default('sum')->after('type');
            $table->decimal('fixed_price', 10, 2)->nullable()->after('pricing_type');
            $table->boolean('in_stop_list')->default(false)->after('is_active');
            $table->text('short_description')->nullable()->after('sort_order');
        });

    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('external_id');

            $table->dropColumn([
                'type',
                'pricing_type',
                'fixed_price',
                'in_stop_list',
                'short_description',
            ]);
        });

    }
};
