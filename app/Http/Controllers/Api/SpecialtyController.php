<?php
// Tên file: app/Http/Controllers/Api/SpecialtyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DoctorAvailability;
use Carbon\Carbon;
// 1. "Gọi" Người Quản Kho (Model) mà chúng ta cần
use App\Models\Specialty;

class SpecialtyController extends Controller
{
    /**
     * Hàm này sẽ chạy khi ai đó gọi GET /api/specialties
     *
     * Tên hàm 'index' là quy ước của Laravel cho "lấy danh sách".
     */
    public function index(Request $request)
    {
        //Bắt đầu câu query VÀ "tải lồng" ('with')
        // các dịch vụ (services) liên quan
        $query = Specialty::with('services');

        //Kiểm tra xem có tham số 'search' trên URL không
        if ($request->has('search')) {
            $searchTerm = $request->input('search');

            // Tìm kiếm theo Tên Chuyên khoa
            $query->where('SpecialtyName', 'like', '%' . $searchTerm . '%');

            // HOẶC tìm kiếm theo Tên Dịch vụ (dùng 'orWhereHas')
            $query->orWhereHas('services', function ($q) use ($searchTerm) {
                $q->where('ServiceName', 'like', '%' . $searchTerm . '%');
            });
        }

        $specialties = $query->get();

        return response()->json($specialties, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lấy chi tiết 1 Chuyên khoa (kèm dịch vụ)
     */
    public function show($id)
    {
        $specialty = Specialty::with('services') // <-- THAY ĐỔI QUAN TRỌNG
            ->findOrFail($id);

        return response()->json($specialty, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * Lấy các slot CÒN TRỐNG theo Chuyên khoa.
     * Chạy khi gọi GET /api/specialties/{id}/availability
     */
    public function getAvailability($id)
    {
        $specialty = Specialty::findOrFail($id);

        //Lấy ra ID của TẤT CẢ Bác sĩ thuộc chuyên khoa này
        // Ví dụ: [1, 5, 8]
        $doctorIds = $specialty->doctors()->pluck('DoctorID');

        // Tìm TẤT CẢ Lịch trống (Slots)
        $availableSlots = DoctorAvailability::whereIn('DoctorID', $doctorIds)
            ->where('Status', '=', 'Available')     
            ->where('StartTime', '>', Carbon::now())
            ->orderBy('StartTime', 'asc')
            ->get();

        return response()->json($availableSlots, 200, [], JSON_UNESCAPED_UNICODE);
    }
}