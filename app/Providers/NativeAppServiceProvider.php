<?php

namespace App\Providers;

use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\Window;
use Native\Laravel\Facades\ChildProcess;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Start the Reverb WebSocket server in the background persistently
        ChildProcess::artisan('reverb:start', 'reverb', null, true);

        Window::open()
            ->width(1280)
            ->height(800)
            ->title('EasyQ Desktop Controller')
            ->showDevTools();
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
