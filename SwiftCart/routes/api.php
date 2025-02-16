<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [UserController::class, 'register'])->name('api.register');
Route::post('login', [UserController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(
    function () {
        Route::post('logout', [UserController::class, 'logout'])->name('api.logout');
        Route::post('user', [UserController::class, 'user'])->name('api.user');
    }
);
