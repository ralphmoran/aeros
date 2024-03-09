<?php

namespace App\Providers;

use Aeros\Src\Classes\ServiceProvider;

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
        app()->singleton('db', \Aeros\Src\Classes\Db::class);
        app()->singleton('queue', \Aeros\Src\Classes\Queue::class);
        app()->singleton('cache', \Aeros\Src\Classes\Cache::class);
        app()->singleton('security', \Aeros\Src\Classes\Security::class);
        app()->singleton('email', \PHPMailer\PHPMailer\PHPMailer::class);
        app()->singleton('router', \Aeros\Src\Classes\Router::class);
        app()->singleton('view', \Aeros\Src\Classes\View::class);
        app()->singleton('component', \Aeros\Src\Classes\Component::class);
        app()->singleton('response', \Aeros\Src\Classes\Response::class);
        app()->singleton('request', \Aeros\Src\Classes\Request::class);
        app()->singleton('redirect', \Aeros\Src\Classes\Redirect::class);
        app()->singleton('event', \Aeros\Src\Classes\Event::class);
        app()->singleton('logger', \Aeros\Src\Classes\Logger::class);
        app()->singleton('file', \Aeros\Src\Classes\File::class);
        app()->singleton('encryptor', \Aeros\Src\Classes\Encryptor::class);
        app()->singleton('session', \Aeros\Src\Classes\Session::class);
        app()->singleton('cookie', \Aeros\Src\Classes\Cookie::class);
        app()->singleton('config', \Aeros\Src\Classes\Config::class);
        app()->singleton('worker', \App\Queues\Workers\AppWorker::class);
        app()->singleton('scheduler', \GO\Scheduler::class);
        app()->singleton('service', \App\Providers\ServiceAgentServiceProvider::class);

        // Register objects only for CLI
        if (isMode('cli')) {
            app()->singleton('console', \Symfony\Component\Console\Application::class);
            app()->singleton('aeros', \Aeros\Src\Classes\Aeros::class);
        }
    }

    public function boot(): void
    {

    }
}
