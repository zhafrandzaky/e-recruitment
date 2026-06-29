<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PublicController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// Public stats for landing page — no auth required
Route::get('/public/stats', [PublicController::class, 'stats']);

// Authentication routes — rate limited against brute force
Route::middleware('throttle:auth')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Authenticated endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Public job listing — no auth required
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{id}', [JobController::class, 'show']);

// HR-only job management
Route::middleware(['auth:sanctum', EnsureRole::class.':hr_admin'])->group(function () {
    Route::post('/jobs', [JobController::class, 'store']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::patch('/jobs/{id}/status', [JobController::class, 'updateStatus']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
});
