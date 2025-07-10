<?php

namespace SmartCms\Redirects\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SmartCms\Redirects\Redirects
 */
class Redirects extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SmartCms\Redirects\Redirects::class;
    }
}
