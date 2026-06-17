<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\ProductService call()
 */
class ProductService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\ProductService::class;
    }
}
