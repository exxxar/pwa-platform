<?php

namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\StoryService call()
 */
class StoryService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\StoryService::class;
    }
}
