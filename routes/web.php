<?php
/*
|-------------------------------------------
| Web Routes
|-------------------------------------------
|
| Here is where you can register web routes. 
|
*/

use Classes\Route;

Route::get("/", "AppController")
    ->withMiddleware([
        Middlewares\BanBotsMiddleware::class,
        Middlewares\CorsMiddleware::class,
        Middlewares\SanitizerMiddleware::class
    ]);

// Route::get("admin:/users/{firstname}/{lastname}", "AppController@testMethod");
