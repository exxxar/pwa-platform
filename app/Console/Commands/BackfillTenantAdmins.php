<?php

namespace App\Console\Commands;

use Database\Seeders\Tenant\BackfillTenantAdminsSeeder;
use Illuminate\Console\Command;

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
