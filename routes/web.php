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
    return view('welcome');
});

// === THÊM ROUTE LOGIN ĐỂ FIX LỖI ===
Route::get('/login', function () {
    return response()->json([
        'error' => 'Unauthenticated',
        'message' => 'Please login to access this resource'
    ], 401);
})->name('login');