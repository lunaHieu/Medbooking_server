<?php
// Tên file: app/Http/Controllers/Api/Doctor/DashboardController.php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- Thêm

class DashboardController extends Controller
{
    /**
     * Lấy các số liệu thống kê cho Doctor Homepage
     * Chạy khi gọi GET /api/doctor/dashboard-stats
     */
    public function index(Request $request)
    {
        // 1. Lấy hồ sơ Bác sĩ (Doctor Profile) của user đang đăng nhập
        $doctor = $request->user()->doctorProfile;

        // 2. Lấy tất cả lịch hẹn của Bác sĩ này
        $appointments = $doctor->appointments(); // Đây là câu query, chưa lấy

        // 3. Đếm (count) dựa trên các trạng thái
        
        // "Tổng số lịch hẹn" (chỉ tính các lịch đã/đang/sẽ diễn ra)
        $totalAppointments = (clone $appointments) // Sao chép câu query
            ->whereIn('Status', ['Pending', 'Confirmed', 'CheckedIn', 'Completed'])
            ->count();
        
        // "Đã khám xong"
        $completedAppointments = (clone $appointments)
            ->where('Status', 'Completed')
            ->count();
            
        // "Đang chờ" (bao gồm Chờ xác nhận và Chờ khám)
        $waitingAppointments = (clone $appointments)
            ->whereIn('Status', ['Pending', 'CheckedIn'])
            ->count();


        // 4. Trả về 1 đối tượng JSON
        return response()->json([
            'total_appointments_count' => $totalAppointments,
            'completed_appointments_count' => $completedAppointments,
            'waiting_appointments_count' => $waitingAppointments,
        ], 200);
    }
}