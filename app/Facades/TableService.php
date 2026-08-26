<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\TableService call()
 */
class TableService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\TableService::class;
    }
}
