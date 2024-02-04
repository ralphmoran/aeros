<?php
/*
|-------------------------------------------
| Web Routes
|-------------------------------------------
|
| Here is where you can register web routes. 
|
*/

use Aeros\Src\Classes\Route;

// Group middlewares: All routes within the calback are going 
// to receive middlewares from 'auth' group (see config('app.middlewares.auth'))
Route::group('auth', function () {
    Route::get(":/profile", "IndexController@showProfile");
});

Route::get(":/", "IndexController");
Route::get(":/list/{userid}/profile", "IndexController@list");
Route::get(":/another/{roleid}/profile/{anotherid}", "IndexController@anotherProfile");

// -- Example #1: Route with a closure|callback

// Route::get(":/", function () {
//     return 'Yes!';
// });

// Route::post("/", "AppController");
