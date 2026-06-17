<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\PaymentService call()
 */
class PaymentService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\PaymentService::class;
    }
}
