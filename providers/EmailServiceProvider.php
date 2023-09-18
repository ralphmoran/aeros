<?php

namespace Providers;

use Classes\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Add event listener for email notification
        app()->event
            ->addEventListener('email.notify', new \Events\EmailNotifierEvent())
            ->addEventListener('email.reminder', new \Events\EmailReminderEvent())
            ->addEventListener('email.followup', new \Events\EmailFollowupEvent());

        // Setup email connection
        // ...
    }
}
