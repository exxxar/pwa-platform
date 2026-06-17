<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\TenantService call()
 */
class TenantService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\TenantService::class;
    }
}
