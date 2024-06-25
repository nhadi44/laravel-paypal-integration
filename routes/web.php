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

Route::get('/', function () {
    $url = null;
    return view('welcome', compact('url'));
});

Route::post('paypal', [\App\Http\Controllers\PaypalController::class, 'paypal'])->name('paypal');
Route::get('success', [\App\Http\Controllers\PaypalController::class, 'success'])->name('success');
Route::get('cancel', [\App\Http\Controllers\PaypalController::class, 'cancel'])->name('cancel');
