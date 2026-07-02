<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

foreach (['localhost', '127.0.0.1'] as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('welcome_central');
        });

        Route::post('/register-tenant', function (Request $request) {
            $request->validate([
                'company' => 'required|string|max:50|alpha_dash|unique:tenants,id',
                'email' => 'required|email|max:255',
            ]);

            $id = strtolower($request->company);

            // Create tenant
            $tenant = Tenant::create(['id' => $id]);
            $domain = "{$id}.localhost";
            $tenant->domains()->create(['domain' => $domain]);

            $port = $request->getPort();
            $portStr = ($port && $port != 80 && $port != 443) ? ":{$port}" : '';

            return response()->json([
                'success' => true,
                'domain' => $domain,
                'redirect_url' => "http://{$domain}{$portStr}/register",
            ]);
        });

        Route::get('/check-tenant', function (Request $request) {
            $request->validate([
                'company' => 'required|string|max:50|alpha_dash',
            ]);

            $id = strtolower($request->company);
            $tenant = Tenant::find($id);

            if (! $tenant) {
                return response()->json([
                    'message' => 'This business domain does not exist.',
                ], 404);
            }

            $hasUsers = false;
            $tenant->run(function () use (&$hasUsers) {
                $hasUsers = User::exists();
            });

            $port = $request->getPort();
            $portStr = ($port && $port != 80 && $port != 443) ? ":{$port}" : '';
            $redirectUrl = $hasUsers
                ? "http://{$id}.localhost{$portStr}/login"
                : "http://{$id}.localhost{$portStr}/register";

            return response()->json([
                'success' => true,
                'redirect_url' => $redirectUrl,
            ]);
        });
    });
}
