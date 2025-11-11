<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExplorerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/explorer', [ExplorerController::class, 'index'])->name('explorer.index');
