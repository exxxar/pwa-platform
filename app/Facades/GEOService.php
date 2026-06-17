<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\GEOService call()
 */
class GEOService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\GEOService::class;
    }
}
