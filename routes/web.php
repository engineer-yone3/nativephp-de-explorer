<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExplorerController;
use App\Http\Controllers\LanguageController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/explorer', [ExplorerController::class, 'index'])->name('explorer.index');
Route::post('/api/file/open', [ExplorerController::class, 'openFile'])->name('file.open');
