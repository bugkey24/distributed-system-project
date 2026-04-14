<?php

use App\Http\Controllers\Api\InfoController;
use Illuminate\Support\Facades\Route;

Route::get('/server-info', [InfoController::class, 'index']);
Route::get('/health', [InfoController::class, 'health']);
Route::post('/requests', [InfoController::class, 'store']);
Route::get('/requests', [InfoController::class, 'list']);
