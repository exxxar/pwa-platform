<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\CashBackService call()
 */
class CashbackService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\CashBackService::class;
    }
}
