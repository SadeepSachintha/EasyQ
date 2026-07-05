<?php

namespace App\Providers;

use App\Http\Middleware\CustomPreventRegularBrowserAccess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Native\Laravel\Http\Middleware\PreventRegularBrowserAccess;
use Stancl\Tenancy\Events\TenancyInitialized;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PreventRegularBrowserAccess::class,
            CustomPreventRegularBrowserAccess::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dynamically override the cached tenancy central connection to match the runtime database default
        config(['tenancy.database.central_connection' => config('database.default')]);

        Vite::prefetch(concurrency: 3);

        Event::listen(TenancyInitialized::class, function (TenancyInitialized $event) {
            URL::defaults(['tenant' => $event->tenancy->tenant->id]);
        });

        RateLimiter::for('checkin', function (Request $request) {
            $tenantId = tenant('id') ?? 'central';

            return Limit::perMinute(5)->by($tenantId.'_'.$request->ip());
        });
    }
}
