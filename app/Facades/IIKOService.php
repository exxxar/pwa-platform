<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\IIKOService call()
 */
class IIKOService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\IIKOService::class;
    }
}
