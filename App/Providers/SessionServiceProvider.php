<?php

namespace Aeros\App\Providers;

use Aeros\Src\Classes\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Processes global session.
     * 
     * The session ONLY works on web servers.
     *
     * @return void
     */
    public function register(): void
    {
        date_default_timezone_set(config('app.timezone'));

        if (! in_array(env('APP_ENV'), ['production'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        }
        
        // Only on web request
        if (strpos(php_sapi_name(), 'cli') === false) {
            session()->start();

            response()->addHeaders(config('session.headers.default'));
        }
    }

    public function boot(): void
    {

    }
}