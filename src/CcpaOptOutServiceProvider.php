<?php

namespace JeffersonGoncalves\CcpaOptOut;

use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\CcpaOptOut\Http\HonorGlobalPrivacyControl;
use JeffersonGoncalves\CcpaOptOut\Http\OptOutController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CcpaOptOutServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ccpa-opt-out')
            ->hasConfigFile()
            ->hasMigrations(['create_ccpa_opt_outs_table']);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(OptOutManager::class);
    }

    public function packageBooted(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('ccpa.gpc', HonorGlobalPrivacyControl::class);

        // @ccpaSaleAllowed ... @endccpaSaleAllowed: ad pixels, data broker tags.
        Blade::if('ccpaSaleAllowed', function (): bool {
            $user = auth()->user();
            $request = request();

            if ($request->attributes->get('ccpa_gpc') === true || $request->header('Sec-GPC') === '1') {
                return false;
            }

            $subject = $user instanceof Model ? $user : $request->cookie((string) config('ccpa-opt-out.route.cookie', 'ccpa_opt_out_id'));

            return $this->app->make(OptOutManager::class)->allowsSaleOrSharing(is_string($subject) || $subject instanceof Model ? $subject : null);
        });

        if (! config('ccpa-opt-out.route.enabled', false)) {
            return;
        }

        if (config('ccpa-opt-out.route.claim_on_login', true)) {
            Event::listen(Login::class, function (Login $event): void {
                $id = request()->cookie((string) config('ccpa-opt-out.route.cookie', 'ccpa_opt_out_id'));

                if (is_string($id) && $id !== '' && $event->user instanceof Model) {
                    $this->app->make(OptOutManager::class)->claim($id, $event->user);
                }
            });
        }

        Route::middleware((array) config('ccpa-opt-out.route.middleware', ['web']))
            ->post((string) config('ccpa-opt-out.route.path', 'ccpa/opt-out'), OptOutController::class)
            ->name('ccpa-opt-out.store');
    }
}
