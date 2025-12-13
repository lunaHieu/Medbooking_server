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
     * Admin tạo một Bác sĩ mới
     * POST /api/admin/doctors
     */
    public function store(Request $request)
    {
        // 1. Validate
        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => 'required|string|max:100|unique:users',
            'PhoneNumber' => 'required|string|max:15|unique:users',
            'Email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'SpecialtyID' => 'required|integer|exists:specialties,SpecialtyID',
            'Degree' => 'required|string|max:100',
            'YearsOfExperience' => 'required|integer|min:0',
            'ProfileDescription' => 'nullable|string',
            'imageURL' => 'nullable|image|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // 2. Tạo User
            $user = new User();
            $user->FullName = $request->FullName;
            $user->Username = $request->Username;
            $user->PhoneNumber = $request->PhoneNumber;
            $user->password = Hash::make($request->password);
            $user->Email = $request->Email;
            $user->Role = 'BacSi';
            $user->Status = 'HoatDong';
            $user->save();

            // 3. Tạo Doctor
            $doctor = new Doctor();
            $doctor->DoctorID = $user->UserID;
            $doctor->SpecialtyID = $request->SpecialtyID;
            $doctor->Degree = $request->Degree;
            $doctor->YearsOfExperience = $request->YearsOfExperience;
            $doctor->ProfileDescription = $request->ProfileDescription;

            // Lưu có /storage/ vào DB
            if ($request->hasFile('imageURL')) {
                $path = $request->file('imageURL')->store('uploads/doctors', 'public');
                //Dùng Storage::url để lấy đường dẫn public (/storage/uploads/...)
                $doctor->imageURL = Storage::url($path);
            }

            $doctor->save();

            DB::commit();

            return response()->json([
                'message' => 'Tạo hồ sơ Bác sĩ thành công!',
                'doctor' => $doctor->load('user', 'specialty')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi tạo bác sĩ', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cập nhật Bác sĩ
     * PUT /api/admin/doctors/{id}
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);
        $user = $doctor->user;

        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => ['required', Rule::unique('users')->ignore($user->UserID, 'UserID')],
            'PhoneNumber' => ['required', Rule::unique('users')->ignore($user->UserID, 'UserID')],
            'Email' => ['required', 'email', Rule::unique('users', 'Email')->ignore($user->UserID, 'UserID')],
            'SpecialtyID' => 'required|integer|exists:specialties,SpecialtyID',
            'Degree' => 'required|string|max:100',
            'YearsOfExperience' => 'required|integer|min:0',
            'imageURL' => 'nullable',
        ]);

        try {
            DB::beginTransaction();

            // Cập nhật User
            $user->FullName = $request->FullName;
            $user->Username = $request->Username;
            $user->PhoneNumber = $request->PhoneNumber;
            $user->Email = $request->Email;
            if ($request->filled('password'))
                $user->password = Hash::make($request->password);
            if ($request->has('Status'))
                $user->Status = $request->Status;
            $user->save();

            // Cập nhật Doctor
            $doctor->SpecialtyID = $request->SpecialtyID;
            $doctor->Degree = $request->Degree;
            $doctor->YearsOfExperience = $request->YearsOfExperience;
            $doctor->ProfileDescription = $request->ProfileDescription;

            // XỬ LÝ ẢNH
            if ($request->hasFile('imageURL')) {
                // A. Xóa ảnh cũ
                if ($doctor->imageURL) {
                    // Vì trong DB đã lưu có /storage/, ta replace đi để lấy path gốc xóa file
                    $oldPath = str_replace('/storage/', '', $doctor->imageURL);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                // B. Lưu ảnh mới
                $path = $request->file('imageURL')->store('uploads/doctors', 'public');

                // C. Lưu vào DB (có /storage/)
                $doctor->imageURL = Storage::url($path);
            }

            $doctor->save();

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật hồ sơ Bác sĩ thành công!',
                'doctor' => $doctor->load('user', 'specialty')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi cập nhật', 'error' => $e->getMessage()], 500);
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
            'imageURL' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240'
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
     * Admin Xóa một Bác sĩ (cả User và Doctor profile).
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