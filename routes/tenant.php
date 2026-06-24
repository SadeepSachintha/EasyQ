<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\QueueController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])
->domain('{tenant}.localhost')
->group(function () {
    
    // Customer-Facing Widget (Walk-in check-in & Queue monitoring)
    Route::get('/', function () {
        $services = \App\Models\Service::all();
        $controller = new QueueController();
        $queueData = $controller->getQueueData();
        return Inertia::render('Tenant/QueueWidget', [
            'services' => $services,
            'initialQueue' => $queueData,
            'tenant' => tenant(),
        ]);
    })->name('tenant.home');

    // Customer Actions
    Route::post('/queue/join', [QueueController::class, 'join'])->name('tenant.queue.join');
    Route::get('/queue/status', [QueueController::class, 'status'])->name('tenant.queue.status');

    // Scoped Auth & Admin Dashboard inside the Tenant Database
    Route::middleware(['auth', 'verified'])->group(function () {
        
        Route::get('/dashboard', function () {
            $services = \App\Models\Service::all();
            $controller = new QueueController();
            $queueData = $controller->getQueueData();
            return Inertia::render('Tenant/Dashboard', [
                'services' => $services,
                'initialQueue' => $queueData,
                'tenant' => tenant(),
            ]);
        })->name('dashboard');

        // Admin Queue Actions
        Route::post('/admin/queue/call', [QueueController::class, 'callNext'])->name('tenant.queue.call');
        Route::post('/admin/queue/complete/{id}', [QueueController::class, 'complete'])->name('tenant.queue.complete');
        Route::post('/admin/queue/noshow/{id}', [QueueController::class, 'noshow'])->name('tenant.queue.noshow');
        Route::delete('/admin/queue/remove/{id}', [QueueController::class, 'remove'])->name('tenant.queue.remove');

        // User Profile Management
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Load Breeze Auth routes under Tenant Context
    require __DIR__.'/tenant_auth.php';
});
