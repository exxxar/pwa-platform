<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\GEOService call()
 */
class GEOService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\GEOService::class;
    }
}
