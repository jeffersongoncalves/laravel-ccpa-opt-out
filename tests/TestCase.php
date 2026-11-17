<?php

namespace JeffersonGoncalves\CcpaOptOut\Tests;

use JeffersonGoncalves\CcpaOptOut\CcpaOptOutServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CcpaOptOutServiceProvider::class,
        ];
    }
}
