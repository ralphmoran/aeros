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

// Group middlewares: All routes within the calback are going 
// to receive middlewares from 'auth' group (see config('app.middlewares.auth'))
Route::group('auth', function () {
    Route::get(":/profile", "IndexController@showProfile");
});

Route::get(":/", "IndexController");
Route::get(":/list/{userid}/profile", "IndexController@list");
Route::get(":/another/{roleid}/profile/{anotherid}", "IndexController@anotherProfile");

Route::get("admin:/", "AppController");
Route::get("admin:/login", "AppController@showForm");
Route::get("admin:/login/{userid}/{profile}", "AppController@profile");

// -- Example #1: Route with a closure|callback

// Route::get(":/", function () {
//     return 'Yes!';
// });

// -- Example #2: Route with a controller and middlewares

// Route::get("admin:/", "AppController")
//     ->withMiddleware([
//         Middlewares\BanBotsMiddleware::class,
//         Middlewares\CorsMiddleware::class,
//         Middlewares\SanitizerMiddleware::class
//     ]);

// -- Example #3: Route with dynamic URL, controller name and method, also, subdomain
// Route::get("admin:/users/{firstname}/{lastname}", "AppController@testMethod");

// Route::post("/", "AppController");
