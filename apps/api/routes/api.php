<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\InterviewController;
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

// ─── Applications (authenticated) ──────────────────────────────────────────

// Applicant: submit application (must be logged in, must be applicant)
Route::middleware(['auth:sanctum', EnsureRole::class.':applicant'])->group(function () {
    Route::post('/jobs/{id}/applications', [ApplicationController::class, 'submit']);
});

// Applicant: own applications (ownership enforced in controller)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/applications/me', [ApplicationController::class, 'myApplications']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::get('/applications/{id}/cv', [ApplicationController::class, 'downloadCv']);
});

// ─── HR-only application management ────────────────────────────────────────

Route::middleware(['auth:sanctum', EnsureRole::class.':hr_admin'])->group(function () {
    Route::get('/jobs/{id}/applications', [ApplicationController::class, 'listByJob']);
    Route::patch('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
});

// ─── HR-only job management ────────────────────────────────────────────────

Route::middleware(['auth:sanctum', EnsureRole::class.':hr_admin'])->group(function () {
    Route::post('/jobs', [JobController::class, 'store']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::patch('/jobs/{id}/status', [JobController::class, 'updateStatus']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
});

// ─── HR-only interview scheduling ──────────────────────────────────────────

Route::middleware(['auth:sanctum', EnsureRole::class.':hr_admin'])->group(function () {
    Route::post('/applications/{id}/interview', [InterviewController::class, 'schedule']);
    Route::patch('/applications/{id}/interview', [InterviewController::class, 'reschedule']);
    Route::delete('/applications/{id}/interview', [InterviewController::class, 'cancel']);
});

// ─── Interview detail (authenticated, both roles) ───────────────────────────

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/applications/{id}/interview', [InterviewController::class, 'show']);
});

// ─── Per-application chat (authenticated, both roles) ───────────────────────
// Ownership enforced in the controller (applicant owner / any HR) — the same
// rule as the WebSocket channel, enforced independently (docs/SECURITY.md 3.2).

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/applications/{id}/messages', [ChatController::class, 'index']);
    Route::post('/applications/{id}/messages', [ChatController::class, 'store']);
});
