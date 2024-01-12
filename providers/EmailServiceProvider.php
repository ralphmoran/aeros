<?php

namespace Aeros\Providers;

use Aeros\Lib\Classes\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Add event listener for email notification
        app()->event
            ->addEventListener('email.notify', \Aeros\Events\EmailNotifierEvent::class)
            ->addEventListener('email.reminder', \Aeros\Events\EmailReminderEvent::class)
            ->addEventListener('email.followup', \Aeros\Events\EmailFollowupEvent::class);

        // Setup email connection
        // ...
    }

    public function boot(): void
    {
        
    }
}
