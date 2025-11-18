<?php
// Tên file: app/Http/Controllers/Api/Staff/DashboardController.php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment; // <-- Thêm
use App\Models\User;         // <-- Thêm
use App\Models\Doctor;       // <-- Thêm
use Carbon\Carbon;           // <-- Thêm (Để xử lý Ngày)

class DashboardController extends Controller
{
    /**
     * Lấy các số liệu thống kê cho Staff Homepage
     * Chạy khi gọi GET /api/staff/dashboard-stats
     */
    public function index()
    {
        $today = Carbon::today();

        $todayAppointments = Appointment::where('Status', 'Confirmed')
            ->whereDate('StartTime', $today)
            ->count();

        $pendingAppointments = Appointment::where('Status', 'Pending')
            ->count();

        $newPatients = User::where('Role', 'BenhNhan')
            ->where('created_at', '>=', $today->copy()->subDays(7))
            ->count();

        $totalDoctors = Doctor::count();

        return response()->json([
            'today_appointments_count' => $todayAppointments,
            'pending_appointments_count' => $pendingAppointments,
            'new_patients_count' => $newPatients,
            'total_doctors_count' => $totalDoctors,
        ], 200);
    }
}