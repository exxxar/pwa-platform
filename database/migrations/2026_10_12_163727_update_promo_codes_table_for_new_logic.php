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
        // === ШАГ 1: Переименование старых колонок ===
        Schema::table('promo_codes', function (Blueprint $table) {
            if (Schema::hasColumn('promo_codes', 'cashback_amount') && !Schema::hasColumn('promo_codes', 'discount')) {
                $table->renameColumn('cashback_amount', 'discount');
            }
            if (Schema::hasColumn('promo_codes', 'activate_price') && !Schema::hasColumn('promo_codes', 'min_order_amount')) {
                $table->renameColumn('activate_price', 'min_order_amount');
            }
            if (Schema::hasColumn('promo_codes', 'max_activation_count') && !Schema::hasColumn('promo_codes', 'usage_limit')) {
                $table->renameColumn('max_activation_count', 'usage_limit');
            }
            if (Schema::hasColumn('promo_codes', 'available_to') && !Schema::hasColumn('promo_codes', 'expires_at')) {
                $table->renameColumn('available_to', 'expires_at');
            }
        });

        // === ШАГ 2: Добавление новых колонок (после того, как переименование завершено) ===
        Schema::table('promo_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('promo_codes', 'discount_type')) {
                $table->string('discount_type', 20)->default('fixed')->after('discount')
                    ->comment('Тип скидки: percent или fixed');
            }
            if (!Schema::hasColumn('promo_codes', 'starts_at')) {
                $table->date('starts_at')->nullable()->after('min_order_amount')
                    ->comment('Дата начала действия промокода');
            }
            if (!Schema::hasColumn('promo_codes', 'for_new_users')) {
                $table->boolean('for_new_users')->default(false)->after('usage_limit')
                    ->comment('Доступен только при первом заказе');
            }
            if (!Schema::hasColumn('promo_codes', 'for_vip')) {
                $table->boolean('for_vip')->default(false)->after('for_new_users')
                    ->comment('Доступен только VIP клиентам');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Откат: удаляем новые колонки
        Schema::table('promo_codes', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('promo_codes', 'discount_type')) $columnsToDrop[] = 'discount_type';
            if (Schema::hasColumn('promo_codes', 'starts_at')) $columnsToDrop[] = 'starts_at';
            if (Schema::hasColumn('promo_codes', 'for_new_users')) $columnsToDrop[] = 'for_new_users';
            if (Schema::hasColumn('promo_codes', 'for_vip')) $columnsToDrop[] = 'for_vip';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Откат: возвращаем старые имена
        Schema::table('promo_codes', function (Blueprint $table) {
            if (Schema::hasColumn('promo_codes', 'discount') && !Schema::hasColumn('promo_codes', 'cashback_amount')) {
                $table->renameColumn('discount', 'cashback_amount');
            }
            if (Schema::hasColumn('promo_codes', 'min_order_amount') && !Schema::hasColumn('promo_codes', 'activate_price')) {
                $table->renameColumn('min_order_amount', 'activate_price');
            }
            if (Schema::hasColumn('promo_codes', 'usage_limit') && !Schema::hasColumn('promo_codes', 'max_activation_count')) {
                $table->renameColumn('usage_limit', 'max_activation_count');
            }
            if (Schema::hasColumn('promo_codes', 'expires_at') && !Schema::hasColumn('promo_codes', 'available_to')) {
                $table->renameColumn('expires_at', 'available_to');
            }
        });
    }
};
