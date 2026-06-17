<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\TenantUserService call()
 */
class TenantUserService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\TenantUserService::class;
    }
}
