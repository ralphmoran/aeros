<?php

namespace Aeros\App\Providers;

use Aeros\Src\Classes\ServiceProvider;

class AppEventListernersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
        // If you require to register a service into the service container
        //
    }

    public function boot(): void
    {
        app()->event
        // ->addEventListener()
        // ...
        // ->addEventListener()
        ->addEventListener('email.reminder', \Aeros\App\Events\EmailReminderEvent::class);
    }
}
