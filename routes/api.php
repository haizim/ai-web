<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/generate', [ApiController::class, 'generate'])->name('api.generate');
Route::get('/regenerate/{id}', [ApiController::class, 'regenerate'])->name('api.regenerate');
Route::post('/edit-page', [ApiController::class, 'editPage'])->name('api.edit-page');
Route::post('/generate-style', [ApiController::class, 'generateStyle'])->name('api.generate-style');
