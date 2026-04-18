<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\SubscriptionMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "Nexus Says Hello";
});

/*
|----------------------------------------
| AUTH (Public)
|----------------------------------------
*/
// Public webhook — no auth, Paymob calls this
Route::post('/webhook/paymob', [SubscriptionController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function(){
     // Plans info — no subscription check, user needs to see this even if expired
    Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
});

 // All your other app routes go inside this middleware
    Route::middleware(SubscriptionMiddleware::class)->group(function () {
        // your app routes here
});

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
