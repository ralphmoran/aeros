<?php

namespace Providers;

use Classes\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Add event listener for email notification
        app()->event
            ->addEventListener('email.notify', \Events\EmailNotifierEvent::class)
            ->addEventListener('email.reminder', \Events\EmailReminderEvent::class)
            ->addEventListener('email.followup', \Events\EmailFollowupEvent::class);

        // Setup email connection
        // ...
    }

    public function boot(): void
    {
        
    }
}
