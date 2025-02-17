<?php

use App\Http\Controllers\Api\HouseholdApplicationController;
use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [UserController::class, 'register'])->name('api.register');
Route::post('login', [UserController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(
    function () {
        Route::post('logout', [UserController::class, 'logout'])->name('api.logout');
        Route::get('user', [UserController::class, 'user'])->name('api.user');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->where('id', '[0-9]+')->name('api.users.destroy');
        Route::put('users/{user}', [UserController::class, 'update'])->where('id', '[0-9]+')->name('api.users.update');

        Route::post('households', [HouseholdController::class, 'store'])->name('api.households.store');
        Route::get('households', [HouseholdController::class, 'index'])->name('api.households.index');
        Route::put('households/{household}', [HouseholdController::class, 'update'])->where('id', '[0-9]+')->name('api.households.update');
        Route::delete('households/{household}', [HouseholdController::class, 'destroy'])->where('id', '[0-9]+')->name('api.households.destroy');

        Route::post('applications', [HouseholdApplicationController::class, 'store'])->name('api.household_applications.store');
        Route::get('applications', [HouseholdApplicationController::class, 'get_applications'])->name('api.household_applications.get_applications');
        Route::post('applications/{application}', [HouseholdApplicationController::class, 'accept_user'])->where('application', '[0-9]+')->name('api.household_applications.accept_user');
        Route::delete('applications/{application}', [HouseholdApplicationController::class, 'destroy'])->where('application', '[0-9]+')->name('api.household_applications.destroy');
    }
);
