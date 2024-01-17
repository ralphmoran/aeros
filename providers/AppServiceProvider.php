<?php

namespace Aeros\Providers;

use Aeros\Lib\Classes\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registers all singleton services available for the application.
     *
     * @return void
     */
    public function register(): void
    {
        // Singletons
        app()->singleton('db', \Aeros\Lib\Classes\Db::class);
        app()->singleton('queue', \Aeros\Lib\Classes\Queue::class);
        app()->singleton('cache', \Aeros\Lib\Classes\Cache::class);
        app()->singleton('security', \Aeros\Lib\Classes\Security::class);
        app()->singleton('email', \PHPMailer\PHPMailer\PHPMailer::class);
        app()->singleton('router', \Aeros\Lib\Classes\Router::class);
        app()->singleton('view', \Aeros\Lib\Classes\View::class);
        app()->singleton('component', \Aeros\Lib\Classes\Component::class);
        app()->singleton('response', \Aeros\Lib\Classes\Response::class);
        app()->singleton('request', \Aeros\Lib\Classes\Request::class);
        app()->singleton('redirect', \Aeros\Lib\Classes\Redirect::class);
        app()->singleton('event', \Aeros\Lib\Classes\Event::class);
        app()->singleton('logger', \Aeros\Lib\Classes\Logger::class);
        app()->singleton('file', \Aeros\Lib\Classes\File::class);
        app()->singleton('encryptor', \Aeros\Lib\Classes\Encryptor::class);
        app()->singleton('session', \Aeros\Lib\Classes\Session::class);
        app()->singleton('cookie', \Aeros\Lib\Classes\Cookie::class);
        app()->singleton('config', \Aeros\Lib\Classes\Config::class);
        app()->singleton('worker', \Aeros\Queues\Workers\AppWorker::class);
        app()->singleton('scheduler', \GO\Scheduler::class);

        // Register objects only for CLI
        if (strpos(PHP_SAPI, 'cli') !== false) {
            app()->singleton('console', \Symfony\Component\Console\Application::class);
            app()->singleton('aeros', \Aeros\Lib\Classes\Aeros::class);
        }
    }

    public function boot(): void
    {

    }
}
