<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\ProductService call()
 */
class ProductService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\ProductService::class;
    }
}
