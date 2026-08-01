<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenant_messages', function (Blueprint $table) {
            // sender_type: 'user', 'admin', 'system'
            $table->string('sender_type')->default('user')->after('dialog_id');
            // sender_id: ID пользователя или админа (nullable, так как для system может быть пустым)
            $table->unsignedBigInteger('sender_id')->nullable()->after('sender_type');
        });
    }

    public function down()
    {
        Schema::table('tenant_messages', function (Blueprint $table) {
            $table->dropColumn(['sender_type', 'sender_id']);
        });
    }
};
