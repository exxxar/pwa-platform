<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\TenantUserService call()
 */
class TenantUserService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\TenantUserService::class;
    }
}
