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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();

            // 🔗 tenant
            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            // 🏷 код
            $table->string('code')->index();

            // 📝 описание
            $table->text('description')->nullable();

            // 🎰 слоты
            $table->integer('slot_amount')->default(0);

            // 💰 cashback
            $table->decimal('cashback_amount', 10, 2)->default(0);

            // 🔢 лимит активаций
            $table->integer('max_activation_count')->nullable();

            // 💸 цена активации
            $table->decimal('activate_price', 10, 2)->nullable();

            // 📅 доступен до
            $table->timestamp('available_to')->nullable();

            // ⚙️ конфиг
            $table->json('config')->nullable();

            // 🔘 активен
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // ⚡ уникальность внутри tenant
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('promo_code_tenant_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promo_code_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tenant_user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 📊 сколько раз использовал
            $table->integer('activation_count')->default(0);

            // 🕓 последний раз
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // ❗ уникальность
            $table->unique(['promo_code_id', 'tenant_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('promo_code_tenant_user');
    }
};
