<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // SỬA: Facedes → Facades

class AuthController extends Controller
{
    /**
     * Xử lý yêu cầu Đăng ký (Register).
     */
    public function register(Request $request)
    {
        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => 'required|string|max:100|unique:users',
            'PhoneNumber' => 'required|string|max:15|unique:users',
            'Email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'FullName' => $request->FullName,
            'Username' => $request->Username,
            'PhoneNumber' => $request->PhoneNumber,
            'Email' => $request->Email,
            'password' => Hash::make($request->password),
            'Role' => 'BenhNhan',
            'Status' => 'HoatDong',
        ]);

        Auth::login($user);

        return response()->json([
            'message' => 'Đăng ký tài khoản thành công!',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string',
        'password' => 'required|string',
    ]);

    // email FE chính là Username
    $user = \App\Models\User::where('Username', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Tài khoản không tồn tại'
        ], 401);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Sai mật khẩu'
        ], 401);
    }

    // login thủ công
    Auth::login($user);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => [
            'UserID' => $user->UserID,
            'FullName' => $user->FullName,
            'Username' => $user->Username,
            'Role' => $user->Role,
        ]
    ]);
}



    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Đăng xuất thành công!'
        ], 200);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = $request->user();

        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
        }

        $path = $request->file('avatar')->store('uploads/avatars', 'public');
        $user->avatar_url = $path;
        $user->save();

        return response()->json([
            'message' => 'Tải ảnh đại diện thành công!',
            'user' => $user
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'FullName' => 'required|string|max:255',
            'DateOfBirth' => 'nullable|date',
            'Gender' => 'nullable|string|in:Nam,Nu,Khac',
            'Address' => 'nullable|string',
            'Email' => ['nullable', 'email', Rule::unique('users')->ignore($user->UserID, 'UserID')],
            'PhoneNumber' => ['required', 'string', Rule::unique('users')->ignore($user->UserID, 'UserID')],
        ]);

        $user->update($request->only(['FullName', 'DateOfBirth', 'Gender', 'Address', 'Email', 'PhoneNumber']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công!', 
            'user' => $user
        ], 200);
    }

    // Thêm hàm testData
    public function testData()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_appointments_count' => 10,
                'completed_appointments_count' => 5,
                'waiting_appointments_count' => 3,
            ]
        ]);
    }
}