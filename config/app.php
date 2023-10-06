<?php

/*
|----------------------------------------------
| Make service container
|----------------------------------------------
|
| All requests land here for future evaluation.
|
*/

$app = Classes\App::getInstance()
    ->setBaseDir(env('APP_ROOT_DIR') ?? dirname(__DIR__));

/*
|----------------------------------------------
| Singleton core objects: DB, Router, etc
|----------------------------------------------
|
*/

$app->singleton('db', Classes\Db::class);
$app->singleton('security', Classes\Security::class);
$app->singleton('email', Classes\Email::class);
$app->singleton('router', Classes\Router::class);
$app->singleton('view', Classes\View::class);
$app->singleton('component', Classes\Component::class);
$app->singleton('response', Classes\Response::class);
$app->singleton('request', Classes\Request::class);
$app->singleton('redirect', Classes\Redirect::class);
$app->singleton('event', Classes\EventDispatcher::class);
$app->singleton('logger', Classes\Logger::class);
$app->singleton('file', Classes\File::class);
$app->singleton('console', Classes\Console::class);
$app->singleton('encryptor', Classes\Encryptor::class);

# App worker - Queues and Jobs
$app->singleton('cache', Classes\Cache::class);
$app->singleton('worker', Workers\AppWorker::class);

/*
|----------------------------------------------
| Register callables
|----------------------------------------------
|
| All callables must be a class with __invoke method defined.
|
*/

// $app->register('logger', SomeClassWithInvoke::class);
// $app->register('logger', AnotherClassWithInvoke::class);

/*
|----------------------------------------------
| Return main app
|----------------------------------------------
|
*/

return $app;
