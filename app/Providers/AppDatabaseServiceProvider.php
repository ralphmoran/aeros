<?php

namespace Aeros\App\Providers;

use Aeros\Src\Classes\ServiceProvider;

class AppDatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        db();
    }
}
