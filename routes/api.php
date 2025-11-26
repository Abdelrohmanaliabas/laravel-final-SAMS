<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // User management routes
    Route::apiResource('users', UserController::class)->middleware('role:admin');

    // adding or removing roles from user
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole'])->middleware('role:admin');
    Route::delete('/users/{user}/roles/{role}', [UserController::class, 'removeRole'])->middleware('role:admin');
});
