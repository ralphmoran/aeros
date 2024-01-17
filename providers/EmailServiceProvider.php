<?php

namespace Aeros\Providers;

use Aeros\Lib\Classes\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        # TODO: Add event listeners to cache, this way, they will be available and this process
        // can be avoided for performance improvements.

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
