<?php
// Tên file: app/Http/Controllers/Api/Admin/UserManagementController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
class UserManagementController extends Controller
{
    /**
     * Admin lấy danh sách TẤT CẢ user (hỗ trợ lọc & tìm kiếm).
     * Chạy khi gọi GET /api/admin/users
     * Ví dụ: /api/admin/users?role=BacSi
     * Ví dụ: /api/admin/users?search=hieu
     */
    public function index(Request $request)
    {
        $query = User::query();

        // 1. Lọc (Filter) theo Vai trò (Role)
        if ($request->has('role')) {
            $query->where('Role', $request->input('role'));
        }

        // 2. Tìm kiếm (Search)
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('FullName', 'like', '%' . $searchTerm . '%')
                    ->orWhere('Username', 'like', '%' . $searchTerm . '%')
                    ->orWhere('PhoneNumber', 'like', '%' . $searchTerm . '%');
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json($users, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Admin lấy chi tiết 1 User.
     * Chạy khi gọi GET /api/admin/users/{id}
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Admin cập nhật 1 User (bao gồm Role và Status).
     * Chạy khi gọi PUT /api/admin/users/{id}
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users')->ignore($user->UserID, 'UserID')
            ],
            'PhoneNumber' => [
                'required',
                'string',
                'max:15',
                Rule::unique('users')->ignore($user->UserID, 'UserID')
            ],
            // Cho phép Admin thay đổi Role và Status
            'Role' => 'required|string|in:BenhNhan,BacSi,NhanVien,QuanTriVien',
            'Status' => 'required|string|in:HoatDong,Khoa',
            'avatar' => 'nullable|image|max:10240',
            'password' => 'nullable|string|min:6',
        ]);

        // Cập nhật
        $user->update($request->only([
            'FullName',
            'Username',
            'PhoneNumber',
            'Role',
            'Status'
            // (Chúng ta không cho cập nhật mật khẩu ở API này)
        ]));
        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ nếu có
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Lưu ảnh mới
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = $path;
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
        return response()->json([
            'message' => 'Cập nhật tài khoản thành công!',
            'user' => $user
        ], 200);
    }
    /**
     * Admin tạo một User mới (Staff, Admin, hoặc Bệnh nhân).
     * KHÔNG dùng để tạo Bác sĩ (vì thiếu thông tin chuyên môn).
     */
    public function store(Request $request)
    {
        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => 'required|string|max:100|unique:users',
            'PhoneNumber' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:6',
            'Email' => 'nullable|string|email|max:255|unique:users',
            'Role' => 'required|string|in:BenhNhan,NhanVien,QuanTriVien', // Không cho tạo BacSi ở đây
            'Status' => 'required|string|in:HoatDong,Khoa',
            'Gender' => 'nullable|string',
            'DateOfBirth' => 'nullable|date',
        ]);

        // Nếu cố tình tạo Bác sĩ ở đây thì báo lỗi (bắt buộc dùng API Doctors)
        if ($request->Role === 'BacSi') {
            return response()->json([
                'message' => 'Vui lòng dùng chức năng "Quản lý Bác sĩ" để tạo tài khoản Bác sĩ.'
            ], 422);
        }

        $user = new User();
        $user->fill($request->all());
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Tạo tài khoản thành công!',
            'user' => $user
        ], 201);
    }

}