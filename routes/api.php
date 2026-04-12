<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "Nexus Says Hello";
});

/*
|----------------------------------------
| AUTH (Public)
|----------------------------------------
*/
Route::prefix('user')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/password/forgot', 'forgot_password');
    Route::post('/password/reset', 'reset_password');
});

/*
|----------------------------------------
| AUTH (Protected)
|----------------------------------------
*/
Route::prefix('user')->middleware('auth:sanctum')->controller(AuthController::class)->group(function () {

    Route::post('/logout', 'logout');
    Route::post('/logout-all', 'logoutAll');
    Route::post('/logout-others', 'logoutOthers');

});

/*
|----------------------------------------
| PROFILE (Protected)
|----------------------------------------
*/
Route::middleware('auth:sanctum')
    ->prefix('profile')
    ->controller(UserController::class)
    ->group(function () {

        Route::get('/', 'profile');
        Route::post('/picture', 'profile_picture');


        Route::patch('/edit', 'edit');
        Route::delete('/destroy', 'destroy');
    });
