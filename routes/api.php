<?php

use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

// Ticket endpoints
Route::post('/tickets/from-order', [TicketController::class, 'createFromOrder']);

// Student Management endpoints
Route::get('/students', [StudentController::class, 'index']);
Route::get('/students/search', [StudentController::class, 'search']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::post('/students/{id}/password', [StudentController::class, 'updatePassword']);
