<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\PromoCodeService call()
 */
class PromoCodeService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\PromoCodeService::class;
    }
}
