<?php

namespace Aeros\Providers;

use Aeros\Lib\Classes\ServiceProvider;

class CreateAppDatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
        // Your logic goes here
        //
    }

    public function boot(): void
    {
        switch (config('db.default')) {
            case 'mssql':
            case 'mysql':
            case 'postgres':
                # TODO: Review DB creation. It works on web request not on CLI
                // db()->exec('CREATE DATABASE IF NOT EXISTS ' . env('DB_DATABASE'));

                # TODO: Create generic tables: crons, jobs, events, etc...
                break;
            case 'sqlite':
                db('sqlite');
                break;
        }
    }
}
