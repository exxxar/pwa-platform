<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\PartnerService call()
 */
class PartnerService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\PartnerService::class;
    }
}
