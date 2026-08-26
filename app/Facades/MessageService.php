<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\MessageService call()
 */
class MessageService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\TenantService::class;
    }
}
