<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\BackfillTenantAdminsSeeder;

class BackfillTenantAdmins extends Command
{
    protected $signature = 'tenants:provision-admins';
    protected $description = 'Создать админов и права для всех существующих тенантов';

    public function handle()
    {
        $this->info('Запуск provision...');
        $this->call('db:seed', ['--class' => BackfillTenantAdminsSeeder::class]);
        return Command::SUCCESS;
    }
}
