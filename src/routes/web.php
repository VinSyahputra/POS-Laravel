<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PrinterSettingController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('dashboard'));

Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
Route::resource('menus', MenuController::class)->except(['create', 'edit', 'show']);
Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
Route::get('/printer-settings', [PrinterSettingController::class, 'show']);
Route::put('/printer-settings', [PrinterSettingController::class, 'update']);
