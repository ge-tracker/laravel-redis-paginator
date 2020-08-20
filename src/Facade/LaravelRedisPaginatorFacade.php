<?php

namespace GeTracker\LaravelRedisPaginator\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @see \GeTracker\LaravelRedisPaginator\LaravelRedisPaginator
 */
class LaravelRedisPaginatorFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \GeTracker\LaravelRedisPaginator\LaravelRedisPaginator::class;
    }
}
