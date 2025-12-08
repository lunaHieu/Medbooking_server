<?php
// Tên file: app/Http/Controllers/Api/DoctorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\DoctorAvailability;
use App\Models\MedicalRecord;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon; // Cho xử lý ngày giờ

class DoctorController extends Controller
{
    /**
     * Lấy danh sách TẤT CẢ bác sĩ.
     * Chạy khi gọi GET /api/doctors
     */
    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'specialty']);

        // 2. Lọc theo Chuyên khoa (nếu có tham số specialty_id)
        if ($request->filled('specialty_id')) {
            $query->where('SpecialtyID', $request->specialty_id);
        }

        // 3. Tìm kiếm theo Tên Bác sĩ (nếu có tham số search)
        if ($request->filled('search')) {
            $search = $request->search;
            // whereHas dùng để lọc dựa trên bảng quan hệ (users)
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('FullName', 'like', "%{$search}%");
            });
        }

        // 4. Thực thi query lấy dữ liệu
        $doctors = $query->get();

        // 5. Trả về JSON
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
    // public function getSpecialtyAvailability($id)
    // {
    //     // Logic: Lấy tất cả Slot trong bảng 'doctor_availability'
    //     // Mà Slot đó thuộc về Bác sĩ (doctor)
    //     // Mà Bác sĩ đó lại thuộc về Chuyên khoa có ID = $id

    //     $slots = DoctorAvailability::whereHas('doctor', function ($query) use ($id) {
    //         $query->where('SpecialtyID', $id);
    //     })
    //         ->where('Status', 'Available')
    //         ->where('StartTime', '>', Carbon::now())
    //         ->with('doctor.user')
    //         ->orderBy('StartTime', 'asc')
    //         ->get();

    //     return response()->json($slots);
    // }
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