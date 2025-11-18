<?php
// Tên file: app/Http/Controllers/Api/Admin/AppointmentManagementController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment; // <-- Thêm Model

class AppointmentManagementController extends Controller
{
    /**
     * Admin lấy TẤT CẢ lịch hẹn của hệ thống.
     * Chạy khi gọi GET /api/admin/all-appointments
     */
    public function index()
    {
        // 1. Lấy TẤT CẢ lịch hẹn, không lọc (filter) theo user
        // Chúng ta Eager Load 'doctor.user' và 'patient' để lấy tên
        $appointments = Appointment::with([
                                'patient', 
                                'doctor.user' // Lấy Bác sĩ (Doctor) và User (Tên) của Bác sĩ đó
                            ])
                            ->orderBy('StartTime', 'desc') // Mới nhất lên đầu
                            ->get();

        // 2. Trả về JSON
        return response()->json($appointments, 200, [], JSON_UNESCAPED_UNICODE);
    }
}