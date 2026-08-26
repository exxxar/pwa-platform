<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Tenants\StoryService call()
 */
class StoryService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Tenants\StoryService::class;
    }
}
