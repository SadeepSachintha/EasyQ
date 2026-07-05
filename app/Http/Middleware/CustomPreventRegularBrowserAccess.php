<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Native\Laravel\Http\Middleware\PreventRegularBrowserAccess as BaseMiddleware;

class CustomPreventRegularBrowserAccess extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('nativephp-internal.running')) {
            return $next($request);
        }

        // Explicitly skip for the cookie-setting route
        if ($request->path() === '_native/api/cookie') {
            return $next($request);
        }

        // Allow all local loopbacks and subdomains (e.g. salon.localhost)
        // to bypass the strict cookie requirement offline
        $host = $request->getHost();
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || $host === '127.0.0.1') {
            return $next($request);
        }

        // Fall back to standard cookie/secret security check
        return parent::handle($request, $next);
    }
}
