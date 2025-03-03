<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\GroceryController;
use App\Http\Controllers\Api\HouseholdApplicationController;
use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('register', [UserController::class, 'register'])->name('api.register');
Route::post('login', [UserController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(
    function () {

        // UserController
        Route::post('logout', [UserController::class, 'logout'])->name('api.logout');
        Route::get('user', [UserController::class, 'user'])->name('api.user');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->where('user', '[0-9]+')->name('api.users.destroy');
        Route::put('users/{user}', [UserController::class, 'update'])->where('user', '[0-9]+')->name('api.users.update');
        Route::put('users/{user}/password', [UserController::class, 'update_password'])->where('user', '[0-9]+')->name('api.users.update_password');

        //HouseholdController
        Route::post('households', [HouseholdController::class, 'store'])->name('api.households.store');
        Route::get('households/{search?}', [HouseholdController::class, 'index'])->name('api.households.index');
        Route::get('households/{household}', [HouseholdController::class, 'show'])->where('household', '[0-9]+')->name('api.households.show');
        Route::get('households/{household}/users', [HouseholdController::class, 'list_users'])->where('household', '[0-9]+')->name('api.households.list_users');
        Route::get('households/{household}/relationship', [HouseholdController::class, 'get_user_relationship'])->where('household', '[0-9]+')->name('api.households.get_user_relationship');
        Route::get('users/{user}/households', [HouseholdController::class, 'list'])->where('user', '[0-9]+')->name('api.households.list');
        Route::put('households/{household}', [HouseholdController::class, 'update'])->where('household', '[0-9]+')->name('api.households.update');
        Route::delete('households/{household}', [HouseholdController::class, 'destroy'])->where('household', '[0-9]+')->name('api.households.destroy');

        //HouseholdApplicationController
        Route::post('households/{household}/applications', [HouseholdApplicationController::class, 'store'])->where('household', '[0-9]+')->name('api.household_applications.store');
        Route::get('users/{user}/applications', [HouseholdApplicationController::class, 'get_sent_applications'])->where('user', '[0-9]+')->name('api.household_applications.get_sent_applications');
        Route::get('users/{user}/applications/households', [HouseholdApplicationController::class, 'get_sent_households'])->where('user', '[0-9]+')->name('api.household_applications.get_sent_households');
        Route::get('households/{household}/applications', [HouseholdApplicationController::class, 'get_received_applications'])->where('household', '[0-9]+')->name('api.household_applications.get_received_applications');
        Route::get('households/{household}/applications/users', [HouseholdApplicationController::class, 'get_received_users'])->where('household', '[0-9]+')->name('api.household_applications.get_received_users');
        Route::post('applications/{application}', [HouseholdApplicationController::class, 'accept_user'])->where('application', '[0-9]+')->name('api.household_applications.accept_user');
        Route::delete('applications/{application}', [HouseholdApplicationController::class, 'destroy'])->where('application', '[0-9]+')->name('api.household_applications.destroy');

        //GroceryController
        Route::post('households/{household}/groceries', [GroceryController::class, 'store'])->where('household', '[0-9]+')->name('api.groceries.store');
        Route::put('households/{household}/groceries/{grocery}', [GroceryController::class, 'update'])->where('household', '[0-9]+')->where('grocery', '[0-9]+')->name('api.groceries.update');
        Route::get('households/{household}/groceries', [GroceryController::class, 'index'])->where('household', '[0-9]+')->name('api.groceries.index');
        Route::get('households/{household}/groceries/{grocery}', [GroceryController::class, 'show'])->where('household', '[0-9]+')->where('grocery', '[0-9]+')->name('api.groceries.show');
        Route::delete('households/{household}/groceries/{grocery}', [GroceryController::class, 'destroy'])->where('household', '[0-9]+')->where('grocery', '[0-9]+')->name('api.groceries.destroy');

        //CommentController
        Route::post('households/{household}/groceries/{grocery}', [CommentController::class, 'store'])->where('household', '[0-9]+')->where('grocery', '[0-9]+')->name('api.comments.store');
        Route::get('households/{household}/groceries/{grocery}/comments', [CommentController::class, 'index'])->where('household', '[0-9]+')->where('grocery', '[0-9]+')->name('api.comments.index');
        Route::delete('households/{household}/groceries/{grocery}/comments/{comment}', [CommentController::class, 'destroy'])->where('household', '[0-9]+')->where('grocery', '[0-9]+')->where('comment', '[0-9]+')->name('api.comments.destroy');
    }
);
