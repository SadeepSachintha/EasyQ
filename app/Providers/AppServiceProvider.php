<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Events\TenancyInitialized;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(TenancyInitialized::class, function (TenancyInitialized $event) {
            URL::defaults(['tenant' => $event->tenancy->tenant->id]);
        });

        RateLimiter::for('checkin', function (Request $request) {
            $tenantId = tenant('id') ?? 'central';
            return Limit::perMinute(5)->by($tenantId . '_' . $request->ip());
        });
    }
}
