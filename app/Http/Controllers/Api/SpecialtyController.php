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
     * (Vì chúng ta đã định nghĩa trong file routes/api.php).
     *
     * Tên hàm 'index' là quy ước của Laravel cho "lấy danh sách".
     */
    public function index(Request $request)
    {
       // 1. Bắt đầu câu query VÀ "tải lồng" ('with')
        // các dịch vụ (services) liên quan
        $query = Specialty::with('services'); // <-- THAY ĐỔI QUAN TRỌNG

        // 2. Kiểm tra xem có tham số 'search' trên URL không
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            
            // Tìm kiếm theo Tên Chuyên khoa
            $query->where('SpecialtyName', 'like', '%' . $searchTerm . '%');

            // HOẶC tìm kiếm theo Tên Dịch vụ (dùng 'orWhereHas')
            $query->orWhereHas('services', function ($q) use ($searchTerm) {
                $q->where('ServiceName', 'like', '%' . $searchTerm . '%');
            });
        }

        // 3. Lấy kết quả
        $specialties = $query->get();

        return response()->json($specialties, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * HÀM MỚI: Lấy chi tiết 1 Chuyên khoa (kèm dịch vụ)
     * (Chúng ta cũng nâng cấp hàm 'show' luôn)
     */
    public function show($id)
    {
        $specialty = Specialty::with('services') // <-- THAY ĐỔI QUAN TRỌNG
                                ->findOrFail($id);
        
        return response()->json($specialty, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * HÀM MỚI: Lấy các slot CÒN TRỐNG theo Chuyên khoa.
     * Chạy khi gọi GET /api/specialties/{id}/availability
     */
    public function getAvailability($id)
    {
        // 1. Tìm Chuyên khoa, nếu không thấy sẽ 404
        $specialty = Specialty::findOrFail($id);

        // 2. Lấy ra ID của TẤT CẢ Bác sĩ thuộc chuyên khoa này
        // Ví dụ: [1, 5, 8]
        $doctorIds = $specialty->doctors()->pluck('DoctorID');

        // 3. Tìm TẤT CẢ Lịch trống (Slots)
        $availableSlots = DoctorAvailability::whereIn('DoctorID', $doctorIds) // <-- Chỉ tìm của các bác sĩ này
            ->where('Status', '=', 'Available')      // <-- Chỉ lấy slot "Còn trống"
            ->where('StartTime', '>', Carbon::now()) // <-- Chỉ lấy slot trong tương lai
            ->orderBy('StartTime', 'asc')            // Sắp xếp (sớm nhất lên đầu)
            ->get();

        // 4. Trả về JSON
        return response()->json($availableSlots, 200, [], JSON_UNESCAPED_UNICODE);
    }
}