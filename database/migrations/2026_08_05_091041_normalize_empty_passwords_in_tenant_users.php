<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Заменяем пустые строки на NULL в поле password
        DB::table('tenant_users')
            ->where('password', '')
            ->update(['password' => null]);
    }

    public function down(): void
    {
        // Откат не нужен — NULL валиден
    }
};
