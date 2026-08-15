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
        Schema::table('user_referrals', function (Blueprint $table) {
            // Один пользователь может иметь только одного реферера на каждый уровень
            // Но чаще всего нужен просто уникальный referred_id (один реферер вообще)
            $table->unique(['referred_id', 'level'], 'unique_referred_level');

            // Альтернатива (если пользователь может иметь только одного реферера вообще):
            // $table->unique('referred_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_referrals', function (Blueprint $table) {
            //
        });
    }
};
