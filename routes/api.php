<?php

use App\Http\Controllers\FundController;
use Illuminate\Support\Facades\Route;


Route::get('/funds', [FundController::class, 'list']);
Route::put('/funds/{id}', [FundController::class, 'update']);
Route::get('/funds/duplicates', [FundController::class, 'duplicates']);