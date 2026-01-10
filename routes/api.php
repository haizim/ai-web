<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ApiMiniAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/generate', [ApiController::class, 'generate'])->name('api.generate');
Route::get('/regenerate/{id}', [ApiController::class, 'regenerate'])->name('api.regenerate');
Route::post('/edit-page', [ApiController::class, 'editPage'])->name('api.edit-page');
Route::post('/generate-style', [ApiController::class, 'generateStyle'])->name('api.generate-style');
Route::post('/generate-preview', [ApiController::class, 'generatePreview'])->name('api.generate-preview');

Route::post('/generate-functionality', [ApiMiniAppController::class, 'generateFunctionality'])->name('api.generate-functionality');
Route::post('/generate-miniapp-style', [ApiMiniAppController::class, 'generateMiniAppStyle'])->name('api.generate-miniapp-style');
Route::post('/generate-miniapp-preview', [ApiMiniAppController::class, 'generatePreview'])->name('api.generate-miniapp-preview');
Route::post('/edit-miniapp', [ApiMiniAppController::class, 'editMiniApp'])->name('api.edit-miniapp');
