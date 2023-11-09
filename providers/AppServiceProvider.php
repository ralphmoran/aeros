<?php

namespace Providers;

use Classes\ServiceProvider;

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
        app()->singleton('db', \Classes\Db::class);
        app()->singleton('security', \Classes\Security::class);
        app()->singleton('email', \Classes\Email::class);
        app()->singleton('router', \Classes\Router::class);
        app()->singleton('view', \Classes\View::class);
        app()->singleton('component', \Classes\Component::class);
        app()->singleton('response', \Classes\Response::class);
        app()->singleton('request', \Classes\Request::class);
        app()->singleton('redirect', \Classes\Redirect::class);
        app()->singleton('event', \Classes\EventDispatcher::class);
        app()->singleton('logger', \Classes\Logger::class);
        app()->singleton('file', \Classes\File::class);
        app()->singleton('console', \Classes\Console::class);
        app()->singleton('encryptor', \Classes\Encryptor::class);
        app()->singleton('session', \Classes\Session::class);
        app()->singleton('cookie', \Classes\Cookie::class);
        app()->singleton('config', \Classes\Config::class);

        // App worker - Queues and Jobs
        app()->singleton('queue', \Classes\Queue::class);
        app()->singleton('cache', \Classes\Cache::class);
        app()->singleton('worker', \Workers\AppWorker::class);

        // Callables
        // $app->singleton('some', SomeClassWithInvoke::class);
        // $app->singleton('another', AnotherClassWithInvoke::class);
    }

    public function boot(): void
    {

    }
}
