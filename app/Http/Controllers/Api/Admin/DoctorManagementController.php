<?php
// Tên file: app/Http/Controllers/Api/Admin/DoctorManagementController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;   // <-- Thêm
use App\Models\Doctor; // <-- Thêm
use Illuminate\Support\Facades\Hash; // <-- Thêm
use Illuminate\Support\Facades\DB;   // <-- Thêm
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class DoctorManagementController extends Controller
{
    /**
     * Admin tạo một Bác sĩ mới (bao gồm User và Doctor profile).
     * Chạy khi gọi POST /api/admin/doctors
     */
    public function store(Request $request)
    {
        // 1. Validate (Kiểm tra) dữ liệu Admin gửi lên
        $request->validate([
            // Dữ liệu cho bảng User
            'FullName' => 'required|string|max:255',
            'Username' => 'required|string|max:100|unique:users',
            'PhoneNumber' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:6',
            
            // Dữ liệu cho bảng Doctor
            'SpecialtyID' => 'required|integer|exists:specialties,SpecialtyID',
            'Degree' => 'required|string|max:100',
            'YearsOfExperience' => 'required|integer|min:0',
            'ProfileDescription' => 'nullable|string',
            'imageURL' => 'nullable|string|max:500',
        ]);

        // 2. Bắt đầu Transaction
        try {
            DB::beginTransaction();

            // 3. TẠO USER (với Role 'BacSi')
            $user = new User();
            $user->FullName = $request->FullName;
            $user->Username = $request->Username;
            $user->PhoneNumber = $request->PhoneNumber;
            $user->password = Hash::make($request->password);
            $user->Role = 'BacSi'; // <-- Chỉ định Role
            $user->Status = 'HoatDong';
            $user->save(); // Lưu User để lấy UserID

            // 4. TẠO DOCTOR (dùng UserID vừa tạo)
            $doctor = new Doctor();
            $doctor->DoctorID = $user->UserID; // <-- Liên kết 1-1
            $doctor->SpecialtyID = $request->SpecialtyID;
            $doctor->Degree = $request->Degree;
            $doctor->YearsOfExperience = $request->YearsOfExperience;
            $doctor->ProfileDescription = $request->ProfileDescription;
            $doctor->imageURL = $request->imageURL;
            $doctor->save();

            // 5. Hoàn tất
            DB::commit();

            // 6. Trả về thông tin (Eager Load)
            $doctor->load('user', 'specialty'); // Tải thông tin user, specialty

            return response()->json([
                'message' => 'Tạo hồ sơ Bác sĩ thành công!',
                'doctor' => $doctor
            ], 201); // 201 Created

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi máy chủ, không thể tạo Bác sĩ.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //api update bac si, chay khi goi PUT /api/admin/doctors/{id}
    public function update(Request $request, $id)
    {
        // 1. Tìm Bác sĩ (DoctorID) và User liên quan
        // findOrFail sẽ tự động 404 nếu không tìm thấy $id
        $doctor = Doctor::findOrFail($id);
        $user = $doctor->user; // Lấy user qua mối quan hệ

        // 2. Validate (Kiểm tra) dữ liệu Admin gửi lên
        $request->validate([
            // Dữ liệu cho bảng User
            'FullName' => 'required|string|max:255',
            'Username' => [
                'required',
                'string',
                'max:100',
                // Luật 'unique' (duy nhất) đặc biệt:
                // "Phải là unique trong bảng 'users', NGOẠI TRỪ (ignore)
                // user có UserID là $user->UserID"
                Rule::unique('users')->ignore($user->UserID, 'UserID')
            ],
            'PhoneNumber' => [
                'required',
                'string',
                'max:15',
                Rule::unique('users')->ignore($user->UserID, 'UserID')
            ],
            
            // Dữ liệu cho bảng Doctor
            'SpecialtyID' => 'required|integer|exists:specialties,SpecialtyID',
            'Degree' => 'required|string|max:100',
            'YearsOfExperience' => 'required|integer|min:0',
            'ProfileDescription' => 'nullable|string',
            'imageURL' => 'nullable|string|max:500',
        ]);

        // 3. Bắt đầu Transaction (vì cập nhật 2 bảng)
        try {
            DB::beginTransaction();

            // 4. Cập nhật bảng User
            $user->FullName = $request->FullName;
            $user->Username = $request->Username;
            $user->PhoneNumber = $request->PhoneNumber;
            $user->save();

            // 5. Cập nhật bảng Doctor
            $doctor->SpecialtyID = $request->SpecialtyID;
            $doctor->Degree = $request->Degree;
            $doctor->YearsOfExperience = $request->YearsOfExperience;
            $doctor->ProfileDescription = $request->ProfileDescription;
            $doctor->imageURL = $request->imageURL;
            $doctor->save();

            // 6. Hoàn tất
            DB::commit();

            // 7. Trả về thông tin (Eager Load)
            $doctor->load('user', 'specialty'); // Tải lại thông tin mới nhất

            return response()->json([
                'message' => 'Cập nhật hồ sơ Bác sĩ thành công!',
                'doctor' => $doctor
            ], 200); // 200 OK

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi máy chủ, không thể cập nhật Bác sĩ.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //api de admin tai anh len cho bac si
    /* HÀM MỚI: Admin tải ảnh đại diện cho Bác sĩ.
     * Chạy khi gọi POST /api/admin/doctors/{id}/upload-image
     */
    public function uploadImage(Request $request, $id)
    {
        // 1. Validate (Kiểm tra) file gửi lên
        $request->validate([
            // 'imageURL' là tên 'Key' chúng ta sẽ dùng trong Postman
            // 'image' = Phải là file ảnh
            // 'mimes' = Chỉ cho phép các định dạng này
            // 'max:2048' = Tối đa 2MB (2048 KB)
            'imageURL' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // 2. Tìm Bác sĩ
        $doctor = Doctor::findOrFail($id);

        // 3. Xóa ảnh cũ (nếu có) để tránh rác
        if ($doctor->imageURL) {
            // 'public' là tên "Disk" (Ổ đĩa) trong Laravel
            // 'Storage::disk('public')->delete(...)' sẽ xóa file trong 'storage/app/public/...'
            Storage::disk('public')->delete($doctor->imageURL);
        }

        // 4. Lưu ảnh mới vào 'storage/app/public/uploads/doctors'
        // 'store' sẽ tự động tạo tên file ngẫu nhiên, an toàn
        // và trả về đường dẫn tương đối (ví dụ: 'uploads/doctors/abc.jpg')
        $path = $request->file('imageURL')->store('uploads/doctors', 'public');

        // 5. Cập nhật đường dẫn MỚI vào database
        $doctor->imageURL = $path;
        $doctor->save();

        // 6. Trả về đường dẫn URL đầy đủ (để Frontend hiển thị)
        return response()->json([
            'message' => 'Tải ảnh lên thành công!',
            // Storage::url($path) sẽ tự động tạo URL đầy đủ
            // ví dụ: 'http://127.0.0.1:8000/storage/uploads/doctors/abc.jpg'
            'image_url' => Storage::url($path) 
        ], 200);
    }
    /**
     * HÀM MỚI: Admin Xóa một Bác sĩ (cả User và Doctor profile).
     * Chạy khi gọi DELETE /api/admin/doctors/{id}
     */
    public function destroy($id)
    {
        // 1. Tìm User có UserID = $id VÀ Role là 'BacSi'
        $user = User::where('UserID', $id)
                    ->where('Role', 'BacSi')
                    ->first(); // Dùng first() thay vì findOrFail()

        // 2. Kiểm tra xem có tìm thấy Bác sĩ không
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy Bác sĩ với ID này.'], 404);
        }

        // 3. (Logic nâng cao: Kiểm tra xem Bác sĩ này
        // có đang liên quan đến Lịch hẹn (Appointments) nào không
        // (Mặc dù 'onDelete' sẽ xử lý, nhưng báo lỗi cho Admin thì tốt hơn)
        
        // if ($user->doctorProfile->appointments()->count() > 0) {
        //     return response()->json(['message' => 'Không thể xoá, Bác sĩ này vẫn còn lịch hẹn.'], 422);
        // }

        // 4. Xóa User
        // Do 'onDelete('cascade')' trong migration của bảng 'doctors',
        // 'doctors' (và 'doctor_availability') sẽ tự động bị xóa theo.
        $user->delete();

        // 5. Trả về thông báo thành công
        return response()->json(['message' => 'Xóa Bác sĩ thành công.'], 200);
    }


}