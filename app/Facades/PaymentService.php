<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\PaymentService call()
 */
class PaymentService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\PaymentService::class;
    }
}
