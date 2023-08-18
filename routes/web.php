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

Route::get("/", "AppController");

// -- Example #1: Route with a closure|callback

// Route::get("/", function () {return 'Yes!';})

// -- Example #2: Route with a controller and middlewares

// Route::get("/", "AppController")
//     ->withMiddleware([
//         Middlewares\BanBotsMiddleware::class,
//         Middlewares\CorsMiddleware::class,
//         Middlewares\SanitizerMiddleware::class
//     ]);

// -- Example #3: Route with dynamic URL, controller name and method, also, subdomain
// Route::get("admin:/users/{firstname}/{lastname}", "AppController@testMethod");

// Route::post("/", "AppController");
