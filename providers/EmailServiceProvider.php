<?php

namespace Providers;

use Classes\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Add event listener for email notification
        app()->event
            ->addEventListener('email.notify', new \Events\EmailNotifier())
            ->addEventListener('email.reminder', new \Events\EmailReminder())
            ->addEventListener('email.followup', new \Events\EmailFollowup());

        // Setup email connection
        // ...
    }
}
