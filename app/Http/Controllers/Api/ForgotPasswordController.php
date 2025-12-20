<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\OtpMail;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }
        $otpCode = rand(100000, 999999);
        Otp::updateOrCreate(
            ['email' => $request->email],
            [
                'otp' => $otpCode,
                'expires_at' => Carbon::now()->addMinutes(10)
            ]
        );
        try {
            Mail::to($request->email)->send(new OtpMail($otpCode));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi gửi mail: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Mã OTP đã được gửi thành công!']);
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'password' => 'required|min:6'
        ]);

        $otpRecord = Otp::where('email', $request->email)->first();

        if (!$otpRecord || $otpRecord->otp != $request->otp) {
            return response()->json(['message' => 'Mã OTP không chính xác.'], 400);
        }

        if (Carbon::now()->isAfter($otpRecord->expires_at)) {
            return response()->json(['message' => 'Mã OTP đã hết hạn.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        $otpRecord->delete();

        return response()->json(['message' => 'Đổi mật khẩu thành công.']);
    }
}
