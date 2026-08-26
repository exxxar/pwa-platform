<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\PromoCodeService call()
 */
class PromoCodeService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\PromoCodeService::class;
    }
}
