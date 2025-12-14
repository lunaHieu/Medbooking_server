<?php
// Tên file: app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
// "Gọi" các công cụ chúng ta cần
use App\Models\User;            // Model User
use Illuminate\Support\Facades\Hash;  // Công cụ Băm (Hash) mật khẩu
use Illuminate\Support\Facades\Auth;  // Công cụ Xác thực

use Illuminate\Support\Facedes\Storage;

class AuthController extends Controller
{
    /**
     * Xử lý yêu cầu Đăng ký (Register).
     */
    public function register(Request $request)
    {
        // 1. Validate (Kiểm tra) dữ liệu đầu vào
        // Báo lỗi nếu dữ liệu không hợp lệ
        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => 'required|string|max:100|unique:users', // Phải là duy nhất
            'PhoneNumber' => 'required|string|max:15|unique:users', // Phải là duy nhất
            'Email' => 'nullable|string|email|max:255|unique:users', // Phải là email & duy nhất
            'password' => 'required|string|min:6', // Phải có ít nhất 6 ký tự
        ]);

        // 2. Nếu dữ liệu hợp lệ, tạo User mới
        $user = new User();
        $user->FullName = $request->FullName;
        $user->Username = $request->Username;
        $user->PhoneNumber = $request->PhoneNumber;
        $user->Email = $request->Email;

        // 3. Băm mật khẩu (rất quan trọng!)
        // KHÔNG BAO GIỜ lưu mật khẩu gốc vào database
        $user->password = Hash::make($request->password);

        $user->Role = 'BenhNhan'; // Mặc định khi đăng ký là Bệnh nhân
        $user->Status = 'HoatDong'; // Mặc định là Hoạt động

        // 4. Lưu User vào database
        $user->save();

        // 5. Trả về thông báo thành công
        return response()->json([
            'message' => 'Đăng ký tài khoản thành công!'
        ], 201); // 201 = Created (Đã tạo thành công)
    }

    /**
     * Xử lý yêu cầu Đăng nhập (Login).
     */
    public function login(Request $request)
    {
        // 1. Validate (Kiểm tra) dữ liệu đầu vào
        $request->validate([
            'Username' => 'required|string', // Cần Username (hoặc Email/SĐT)
            'password' => 'required|string',
        ]);

        // 2. Thử xác thực
        // Chúng ta lấy Username và password từ request
        $credentials = $request->only('Username', 'password');

        // 3. Dùng "cảnh sát" Auth::attempt để kiểm tra
        // Auth::attempt sẽ tự động:
        // - Tìm user có 'Username' = $request->Username
        // - Hash $request->password
        // - So sánh với 'password' (đã hash) trong database
        if (Auth::attempt($credentials)) {
            // Xác thực thành công!

            // Lấy thông tin user vừa login
            $user = $request->user();

            // 4. Tạo "Chìa khóa" (API Token) cho user
            // Đặt tên token là 'api-token'
            $token = $user->createToken('api-token')->plainTextToken;

            // 5. Trả về Chìa khóa và Thông tin User
            return response()->json([
                'user' => $user,
                'token' => $token
            ], 200); // 200 = OK

        } else {
            // Xác thực thất bại!
            // 6. Trả về lỗi
            return response()->json([
                'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác.'
            ], 401); // 401 = Unauthorized (Không có quyền)
        }
    }

    /**
     * Xử lý yêu cầu Đăng xuất (Logout).
     */
    public function logout(Request $request)
    {
        //lay thong tin dang nhap cua user
        $user = $request->user();

        //vo hieu hoa token hien tai
        $user->currentAccessToken()->delete();

        //tra ve thong bao dang xuat done
        return response()->json([
            'message' => 'Đăng xuất thành công!'
        ], 200);
    }

    /**
     * Bệnh nhân tải ảnh đại diện.
     */
    public function uploadAvatar(Request $request)
    {
        //Validate (Kiểm tra) file gửi lên
        $request->validate([
            // 'avatar' là tên 'Key' chúng ta sẽ dùng trong Postman
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048' // Tối đa 2MB
        ]);

        //Lấy user đang đăng nhập
        $user = $request->user();

        //Xóa ảnh cũ (nếu có) để tránh rác
        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
        }

        //Lưu ảnh mới vào 'storage/app/public/uploads/avatars'
        $path = $request->file('avatar')->store('uploads/avatars', 'public');

        //Cập nhật đường dẫn MỚI vào bảng 'users'
        $user->avatar_url = $path;
        $user->save();

        //Trả về thông tin user đã cập nhật
        return response()->json([
            'message' => 'Tải ảnh đại diện thành công!',
            'user' => $user
        ], 200);
    }
    /**
     * User tự cập nhật thông tin cá nhân
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'FullName' => 'required|string|max:255',
            'DateOfBirth' => 'nullable|date',
            'Gender' => 'nullable|string',
            'Address' => 'nullable|string',
            'Email' => ['nullable', 'email', \Illuminate\Validation\Rule::unique('users')->ignore($user->UserID, 'UserID')],
            'PhoneNumber' => ['required', 'string', \Illuminate\Validation\Rule::unique('users')->ignore($user->UserID, 'UserID')],
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        // Cập nhật (chỉ các trường cho phép)
        $user->update($request->only(['FullName', 'DateOfBirth', 'Gender', 'Address', 'Email', 'PhoneNumber']));

        return response()->json(['message' => 'Cập nhật hồ sơ thành công!', 'user' => $user], 200);
    }
    /**
     * Lấy danh sách người thân
     */
    public function getFamilyMembers(Request $request)
    {
        $user = $request->user();
        //Lấy danh sách qua relationship
        $members = $user->familyMembers;
        $data = $members->map(function ($m) {
            return [
                'UserID' => $m->UserID,
                'FullName' => $m->FullName,
                'PhoneNumber' => $m->PhoneNumber,
                'Email' => $m->Email,
                'DateOfBirth' => $m->DateOfBirth,
                'Gender' => $m->Gender,
                'avatar_url' => $m->avatar_url,
                'RelationType' => $m->pivot->RelationType,
            ];
        });
        return response()->json($data);
    }
    /**
     * Thêm thành viên
     */
    public function addFamilyMembers(Request $request)
    {
        $request->validate([
            'RelativeUserID' => 'required|integer|exists:users,UserID',
            'RelationType' => 'required|string|max:50'
        ]);
        $currentUser = $request->user();
        $relativeId = $request->RelativeUserID;
        if ($currentUser->UserID == $relativeId) {
            return response()->json(['message' => 'Bạn không thể thêm chính mình vào gia đình.'], 400);
        }
        $exists = \Illuminate\Support\Facades\DB::table('user_relations')
            ->where('UserID', $currentUser->UserID)
            ->where('RelativeUserID', $relativeId)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Người này đã có trong danh sách.'], 400);
        }
        //Thực hiện liên kết
        $currentUser->familyMembers()->attach($relativeId, [
            'RelationType' => $request->RelationType
        ]);
        return response()->json(['message' => 'Thêm thành viên thành công!'], 201);
    }
    /**
     * Xóa thành viên khỏi danh sách (Hủy liên kết)
     */
    public function removeFamilyMember(Request $request, $id)
    {
        $currentUser = $request->user();
        $currentUser->familyMembers()->detach($id);
        return response()->json(['message' => 'Đã xáo thành viên khỏi danh sách.']);
    }
    /**
     * Tìm kiếm thành viên
     */
    public function searchUserPublic(Request $request)
    {
        $query = $request->input('query'); // SĐT hoặc Email gửi lên

        if (!$query) {
            return response()->json([]);
        }

        // Chỉ tìm theo SĐT hoặc Email chính xác để bảo mật thông tin
        $users = User::where('PhoneNumber', $query)
            ->orWhere('Email', $query)
            ->select('UserID', 'FullName', 'PhoneNumber', 'Email', 'avatar_url', 'Role')
            ->get();

        // Lọc bỏ chính mình khỏi kết quả
        $currentUserId = $request->user()->UserID;
        $users = $users->filter(function ($u) use ($currentUserId) {
            return $u->UserID !== $currentUserId;
        })->values();

        return response()->json($users);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác.'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công!'
        ]);
    }
}