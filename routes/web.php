<?php

use App\Http\Controllers\Home;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', 'auth/login');

// Route::middleware(['auth', 'verified'])->group(fn () => Route::get('/home', Home::class)->name('home'));
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/home', Home::class)->name('home');

    Route::get('/page/create2', [\App\Http\Controllers\PageController::class, 'create2'])->name('page.create2');
    Route::resource('page', \App\Http\Controllers\PageController::class);
});
Route::get('/p/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->name('p.show');

include __DIR__.'/auth.php';
include __DIR__.'/my.php';
