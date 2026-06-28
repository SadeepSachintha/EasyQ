<?php

namespace App\Providers;

use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Dynamically override the cached tenancy central connection to match the runtime database default
        config(['tenancy.database.central_connection' => config('database.default')]);

        Window::open()
            ->width(1280)
            ->height(800)
            ->title('EasyQ Desktop Controller');
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
