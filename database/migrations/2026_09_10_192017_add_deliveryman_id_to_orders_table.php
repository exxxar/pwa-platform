<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Добавляем поле ПОСЛЕ delivery_service_info (перед deliveryman_info)
            // Предполагаем, что курьеры хранятся в таблице tenant_users.
            // Если у вас отдельная таблица deliverymen, замените 'tenant_users' на 'deliverymen'
            $table->foreignId('deliveryman_id')
                ->nullable()
                ->after('delivery_service_info')
                ->constrained('tenant_users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['deliveryman_id']);
            $table->dropColumn('deliveryman_id');
        });
    }
};
