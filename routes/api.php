<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\V1\ShowController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\V1\ApiSchedule;
use App\Http\Controllers\V1\StatsController;
use App\Http\Controllers\V1\TeacherStatsController;
use App\Http\Controllers\V1\LearningOutcomeController;

// Auth Routes
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'store']);

// Schedule Routes
Route::get('/schedule', [ShowController::class, 'show']);
Route::post('/schedule', [ScheduleController::class, 'createSchedule'])->middleware('auth:sanctum');
Route::put('/schedule/{scheduleId}', [ScheduleController::class, 'editSchedule'])->middleware('auth:sanctum');
// Learning Outcomes
Route::get('/learning-outcomes', [LearningOutcomeController::class, 'index'])->middleware('auth:sanctum');

// Teacher Stats
Route::get('/teacher-stats', [TeacherStatsController::class, 'index'])->middleware('auth:sanctum');

// Users
Route::get('/users', [UserController::class, 'index'])->middleware('auth:sanctum');
Route::post('/users', [UserController::class, 'store'])->middleware('auth:sanctum');

// Stats
Route::get('/stats', [StatsController::class, 'index'])->middleware('auth:sanctum');

Route::get('/dev', [ApiSchedule::class, 'generate']);
