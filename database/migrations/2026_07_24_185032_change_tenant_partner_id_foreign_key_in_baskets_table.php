<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baskets', function (Blueprint $table) {
            // 1. Удаляем старый внешний ключ, ссылающийся на partners
            $table->dropForeign('baskets_tenant_partner_id_foreign');

            // 2. Создаем новый внешний ключ, ссылающийся на tenants
            $table->foreign('tenant_partner_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('baskets', function (Blueprint $table) {
            // Откат: удаляем новый ключ и возвращаем старый
            $table->dropForeign(['tenant_partner_id']);

            $table->foreign('tenant_partner_id')
                ->references('id')
                ->on('partners')
                ->onDelete('set null');
        });
    }
};
