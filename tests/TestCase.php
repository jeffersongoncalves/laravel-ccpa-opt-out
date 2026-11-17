<?php

namespace JeffersonGoncalves\CcpaOptOut\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\Ccpa\CcpaServiceProvider;
use JeffersonGoncalves\CcpaOptOut\CcpaOptOutServiceProvider;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LoadsPackageMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            CcpaServiceProvider::class,
            CcpaOptOutServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('ccpa-opt-out.route.enabled', true);
        $app['config']->set('ccpa-opt-out.route.middleware', ['web']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(CcpaOptOutServiceProvider::class);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }
}
