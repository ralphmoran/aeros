<?php

namespace Aeros\Providers;

use Aeros\Lib\Classes\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        # TODO: Add event listeners to cache, this way, they will be available and this process
        // can be avoided for performance improvements.

        // Setup email connection
        // ...
    }

    public function boot(): void
    {
        
    }
}
