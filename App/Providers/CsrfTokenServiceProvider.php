<?php

namespace Aeros\App\Providers;

use Aeros\Src\Classes\ServiceProvider;
use Ramsey\Uuid\Uuid;

class CsrfTokenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! isset(session()->csrf_token)) {
            // session()->csrf_token = bin2hex(random_bytes(32));
            session()->csrf_token = Uuid::uuid4();
        }
    }

    public function boot(): void
    {
        //
        // If you require to boot a service after all services were registered
        //
    }
}
