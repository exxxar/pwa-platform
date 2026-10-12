<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // === ШАГ 1: Добавляем новые колонки ===
        Schema::table('promo_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('promo_codes', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0.00)->after('description');
            }
            if (!Schema::hasColumn('promo_codes', 'min_order_amount')) {
                $table->decimal('min_order_amount', 10, 2)->nullable()->after('discount');
            }
            if (!Schema::hasColumn('promo_codes', 'usage_limit')) {
                $table->integer('usage_limit')->default(0)->after('min_order_amount');
            }
            if (!Schema::hasColumn('promo_codes', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('usage_limit');
            }

            // Совершенно новые колонки
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

        // === ШАГ 2: Копируем данные из старых колонок в новые (если они существуют) ===
        if (Schema::hasColumn('promo_codes', 'cashback_amount')) {
            DB::statement('UPDATE promo_codes SET discount = cashback_amount');
        }
        if (Schema::hasColumn('promo_codes', 'activate_price')) {
            DB::statement('UPDATE promo_codes SET min_order_amount = activate_price WHERE min_order_amount IS NULL');
        }
        if (Schema::hasColumn('promo_codes', 'max_activation_count')) {
            DB::statement('UPDATE promo_codes SET usage_limit = max_activation_count WHERE usage_limit = 0 AND max_activation_count IS NOT NULL');
        }
        if (Schema::hasColumn('promo_codes', 'available_to')) {
            DB::statement('UPDATE promo_codes SET expires_at = available_to WHERE expires_at IS NULL AND available_to IS NOT NULL');
        }

        // === ШАГ 3: Удаляем старые колонки ===
        Schema::table('promo_codes', function (Blueprint $table) {
            if (Schema::hasColumn('promo_codes', 'cashback_amount')) {
                $table->dropColumn('cashback_amount');
            }
            if (Schema::hasColumn('promo_codes', 'activate_price')) {
                $table->dropColumn('activate_price');
            }
            if (Schema::hasColumn('promo_codes', 'max_activation_count')) {
                $table->dropColumn('max_activation_count');
            }
            if (Schema::hasColumn('promo_codes', 'available_to')) {
                $table->dropColumn('available_to');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем старые колонки
        Schema::table('promo_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('promo_codes', 'cashback_amount')) {
                $table->decimal('cashback_amount', 10, 2)->default(0.00)->after('description');
            }
            if (!Schema::hasColumn('promo_codes', 'activate_price')) {
                $table->decimal('activate_price', 10, 2)->nullable()->after('cashback_amount');
            }
            if (!Schema::hasColumn('promo_codes', 'max_activation_count')) {
                $table->integer('max_activation_count')->nullable()->after('activate_price');
            }
            if (!Schema::hasColumn('promo_codes', 'available_to')) {
                $table->timestamp('available_to')->nullable()->after('max_activation_count');
            }
        });

        // Копируем данные обратно
        if (Schema::hasColumn('promo_codes', 'discount')) {
            DB::statement('UPDATE promo_codes SET cashback_amount = discount');
        }
        if (Schema::hasColumn('promo_codes', 'min_order_amount')) {
            DB::statement('UPDATE promo_codes SET activate_price = min_order_amount WHERE activate_price IS NULL AND min_order_amount IS NOT NULL');
        }
        if (Schema::hasColumn('promo_codes', 'usage_limit')) {
            DB::statement('UPDATE promo_codes SET max_activation_count = usage_limit WHERE max_activation_count IS NULL AND usage_limit IS NOT NULL');
        }
        if (Schema::hasColumn('promo_codes', 'expires_at')) {
            DB::statement('UPDATE promo_codes SET available_to = expires_at WHERE available_to IS NULL AND expires_at IS NOT NULL');
        }

        // Удаляем новые колонки
        Schema::table('promo_codes', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('promo_codes', 'discount')) $columnsToDrop[] = 'discount';
            if (Schema::hasColumn('promo_codes', 'discount_type')) $columnsToDrop[] = 'discount_type';
            if (Schema::hasColumn('promo_codes', 'min_order_amount')) $columnsToDrop[] = 'min_order_amount';
            if (Schema::hasColumn('promo_codes', 'starts_at')) $columnsToDrop[] = 'starts_at';
            if (Schema::hasColumn('promo_codes', 'usage_limit')) $columnsToDrop[] = 'usage_limit';
            if (Schema::hasColumn('promo_codes', 'for_new_users')) $columnsToDrop[] = 'for_new_users';
            if (Schema::hasColumn('promo_codes', 'for_vip')) $columnsToDrop[] = 'for_vip';
            if (Schema::hasColumn('promo_codes', 'expires_at')) $columnsToDrop[] = 'expires_at';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
