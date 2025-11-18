<?php
// Tên file: app/Http/Controllers/Api/DoctorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// 1. "Gọi" các Model chúng ta cần
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon; // Cho xử lý ngày giờ
class DoctorController extends Controller
{
    /**
     * Lấy danh sách TẤT CẢ bác sĩ.
     * Chạy khi gọi GET /api/doctors
     */
    public function index()
    {
        // 2. Eager Loading
        // Lấy TẤT CẢ Doctors, "NHƯNG"
        // "with('user')": Lấy luôn thông tin 'user' liên quan
        // "with('specialty')": Lấy luôn thông tin 'specialty' liên quan
        $doctors = Doctor::with(['user', 'specialty'])->get();

        // 3. Trả về JSON (sửa lỗi tiếng Việt)
        return response()->json($doctors, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lấy danh sách các slot khả dụng của bác sĩ theo ID.
     * Chạy khi gọi GET /api/doctors/{id}/availability
     */
    public function getAvailability($id) //tu lay id tu url
    {
        // 3. Lấy các slot, nhưng có 2 ĐIỀU KIỆN LỌC (filter)
        $availableSlots = Doctor::findOrFail($id) // Tìm Bác sĩ có ID này
            ->availabilitySlots() // Lấy các slot qua Mối quan hệ
            ->where('Status', '=', 'Available') // ĐK 1: Chỉ lấy slot "Còn trống"
            ->where('StartTime', '>', Carbon::now()) // ĐK 2: Chỉ lấy slot trong tương lai
            ->orderBy('StartTime', 'asc') // Sắp xếp (sớm nhất lên đầu)
            ->get();

        // 4. Trả về JSON
        return response()->json($availableSlots, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function show($id)
    {
        // 1. Dùng findOrFail để tìm bác sĩ có ID này.
        // Nếu không tìm thấy, tự động trả về lỗi 404 Not Found.
        $doctor = Doctor::with(['user', 'specialty'])
                        ->findOrFail($id);

        // 2. Trả về JSON (sửa lỗi tiếng Việt)
        return response()->json($doctor, 200, [], JSON_UNESCAPED_UNICODE);
    }

}