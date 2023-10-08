<?php

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

Route::name('csv.')->group(function () {
    Route::get('/', [\App\Http\Controllers\UploadController::class, 'index'])->name('index');
    Route::post('/csv-upload', [\App\Http\Controllers\UploadController::class, 'csvUpload'])->name('upload');
});

