<?php
/*
|-------------------------------------------
| API Routes
|-------------------------------------------
|
| Here is where you can register API routes. 
|
*/

use Aeros\Src\Classes\Route;

// Both routes work in the same way
Route::get("api:/test/profile", "ApiController");
Route::get(":/api/test/profile", "ApiController");
