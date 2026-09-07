<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SimulationController;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard',              [DashboardController::class,  'index'])      ->name('dashboard');
Route::get('/transactions',           [TransactionController::class,'index'])      ->name('transactions.index');
Route::get('/transactions/{id}',      [TransactionController::class,'show'])       ->name('transactions.show');
Route::get('/blocklist',              [TransactionController::class,'blocklist'])  ->name('blocklist');
Route::get('/upload',                 [TransactionController::class,'uploadForm']) ->name('upload.form');
Route::post('/upload',                [TransactionController::class,'uploadCsv'])  ->name('upload.csv');
Route::post('/transactions/{id}/ignore', [TransactionController::class, 'ignore'])->name('transactions.ignore');

// ──── Simulation Routes ────────────────────────────────────
Route::get('/simulation',                  [SimulationController::class, 'uploadForm']) ->name('simulation.form');
Route::post('/simulation/upload',          [SimulationController::class, 'uploadCsv'])  ->name('simulation.upload');
Route::get('/simulation/{batch}',          [SimulationController::class, 'show'])       ->name('simulation.show');
Route::post('/simulation/{batch}/next',    [SimulationController::class, 'next'])       ->name('simulation.next');