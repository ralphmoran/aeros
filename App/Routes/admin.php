<?php
/*
|-------------------------------------------
| Admin Routes
|-------------------------------------------
|
| Here is where you can register admin routes. 
|
*/

use Aeros\Src\Classes\Route;

Route::get("admin:/", "AppController");
Route::get("admin:/login", "AppController@showForm");
Route::get("admin:/login/{userid}/{profile}", "AppController@profile");

// These will work the same way as the ones with subdomain

Route::get(":/admin", "AppController");
Route::get(":/admin/login", "AppController@showForm");
Route::get(":/admin/login/{userid}/{profile}", "AppController@profile");

// -- Example #2: Route with a controller and middlewares

// Route::get("admin:/", "AppController")
//     ->withMiddleware([
//         \App\Middlewares\BanBotsMiddleware::class,
//         \App\Middlewares\CorsMiddleware::class,
//         \App\Middlewares\SanitizerMiddleware::class
//     ]);

// -- Example #3: Route with dynamic URL, controller name and method, also, subdomain
// Route::get("admin:/users/{firstname}/{lastname}", "AppController@testMethod");
