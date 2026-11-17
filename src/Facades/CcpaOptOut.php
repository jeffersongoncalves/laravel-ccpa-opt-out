<?php

namespace JeffersonGoncalves\CcpaOptOut\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\CcpaOptOut\CcpaOptOut
 */
class CcpaOptOut extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-ccpa-opt-out';
    }
}
