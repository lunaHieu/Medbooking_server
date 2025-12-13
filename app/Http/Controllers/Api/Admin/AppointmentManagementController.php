<?php
// Tên file: app/Http/Controllers/Api/Admin/SpecialtyController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Specialty;
use Illuminate\Support\Facades\Storage; // <-- Quan trọng: Để xử lý file ảnh
use App\Models\Appointment;
class AppointmentManagementController extends Controller
{
    /**
     * Admin Thêm 1 Chuyên khoa mới.
     * Chạy khi gọi POST /api/admin/specialties
     */
    public function index()
    {
        // Lấy tất cả lịch hẹn kèm thông tin Bệnh nhân và Bác sĩ
        // 'patient' và 'doctor.user' là tên các relation trong Model Appointment
        $appointments = Appointment::with(['patient', 'doctor.user', 'service'])->get();
        
        return response()->json($appointments);
    }
    public function store(Request $request)
    {
        $request->validate([
            'SpecialtyName' => 'required|string|max:255|unique:specialties',
            'Description' => 'nullable|string',
            // Validate file ảnh: Phải là ảnh, tối đa 2MB
            'imageURL' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        // --- Xử lý Upload Ảnh ---
        $path = null;
        if ($request->hasFile('imageURL')) {
            // Lưu vào folder 'public/uploads/specialties'
            $path = $request->file('imageURL')->store('uploads/specialties', 'public');
        }
        // ------------------------

        $specialty = new Specialty();
        $specialty->SpecialtyName = $request->SpecialtyName;
        $specialty->Description = $request->Description;
        
        // Nếu có ảnh thì lưu đường dẫn đầy đủ, nếu không thì null
        $specialty->imageURL = $path ? Storage::url($path) : null;
        
        $specialty->save();

        return response()->json([
            'message' => 'Tạo chuyên khoa thành công!',
            'specialty' => $specialty
        ], 201);
    }

    /**
     * Admin Cập nhật 1 Chuyên khoa.
     * Chạy khi gọi PUT /api/admin/specialties/{id}
     * (Frontend nhớ gửi _method: PUT trong FormData)
     */
    public function update(Request $request, $id)
    {
        $specialty = Specialty::findOrFail($id);

        $request->validate([
            'SpecialtyName' => 'required|string|max:255|unique:specialties,SpecialtyName,' . $id . ',SpecialtyID',
            'Description' => 'nullable|string',
            'imageURL' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // --- Xử lý Ảnh mới ---
        if ($request->hasFile('imageURL')) {
            // 1. Xóa ảnh cũ nếu có (để dọn rác server)
            // Lưu ý: Đường dẫn trong DB là URL đầy đủ (/storage/...), 
            // ta cần chuyển về đường dẫn tương đối để xóa.
            if ($specialty->imageURL) {
                $oldPath = str_replace('/storage/', '', $specialty->imageURL);
                Storage::disk('public')->delete($oldPath);
            }

            // 2. Lưu ảnh mới
            $path = $request->file('imageURL')->store('uploads/specialties', 'public');
            $specialty->imageURL = Storage::url($path);
        }
        // ---------------------

        $specialty->SpecialtyName = $request->SpecialtyName;
        $specialty->Description = $request->Description;
        $specialty->save();

        return response()->json([
            'message' => 'Cập nhật chuyên khoa thành công!',
            'specialty' => $specialty
        ], 200);
    }

    /**
     * Admin Xoá 1 Chuyên khoa.
     * Chạy khi gọi DELETE /api/admin/specialties/{id}
     */
    public function destroy($id)
    {
        $specialty = Specialty::findOrFail($id);

        // Kiểm tra ràng buộc dữ liệu
        if ($specialty->doctors()->count() > 0) {
            return response()->json(['message' => 'Không thể xoá chuyên khoa này, vẫn còn bác sĩ đang liên kết.'], 422);
        }
        
        // Xóa ảnh khi xóa chuyên khoa
        if ($specialty->imageURL) {
            $oldPath = str_replace('/storage/', '', $specialty->imageURL);
            Storage::disk('public')->delete($oldPath);
        }

        $specialty->delete();

        return response()->json(['message' => 'Xoá chuyên khoa thành công.'], 200); 
    }
}