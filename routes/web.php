<?php

use App\Models\Tenant;
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

            return response()->json([
                'success' => true,
                'domain' => $domain,
                'redirect_url' => "http://{$domain}:8000/register",
            ]);
        });
    });
}
