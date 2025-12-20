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

Route::get('/login', function () {
    return response()->json([
        'error' => 'Unauthenticated',
        'message' => 'Please login to access this resource'
    ], 401);
})->name('login');
Route::get('/test-mail', function () {
    try {
        Mail::raw('Đây là email kiểm tra từ Laravel!', function ($msg) {
            $msg->to('legiahung2908@gmail.com') 
                ->subject('Test Email Laravel');
        });
        return 'Gửi mail thành công!';
    } catch (\Exception $e) {
        return 'Lỗi: ' . $e->getMessage();
    }
});
Route::get('/test-view', function () {
    if (view()->exists('emails.otp')) {
        return "Tìm thấy view! <br>" . view('emails.otp', ['otp' => '123456']);
    } else {
        return "KHÔNG tìm thấy view 'emails.otp'. Hãy kiểm tra lại tên file/thư mục.";
    }
});