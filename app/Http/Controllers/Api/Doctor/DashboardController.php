<?php
// Tên file: app/Http/Controllers/Api/Doctor/DashboardController.php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
<<<<<<< HEAD
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
=======
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DashboardController extends Controller
{
   public function index(Request $request)
{
    try {
        $doctorId = $request->user()->UserID;
        
        // Lấy dữ liệu thật từ database
        $total = DB::table('appointments')
            ->where('DoctorID', $doctorId)
            ->whereIn('Status', ['Pending', 'Confirmed', 'InProgress', 'Completed', 'CheckedIn'])
            ->count();
        
        $completed = DB::table('appointments')
            ->where('DoctorID', $doctorId)
            ->where('Status', 'Completed')
            ->count();
            
        $waiting = DB::table('appointments')
            ->where('DoctorID', $doctorId)
            ->whereIn('Status', ['Pending', 'Confirmed', 'CheckedIn'])
            ->count();
            
        $inProgress = DB::table('appointments')
            ->where('DoctorID', $doctorId)
            ->where('Status', 'InProgress')
            ->count();
        
        $today = DB::table('appointments')
            ->where('DoctorID', $doctorId)
            ->whereDate('StartTime', date('Y-m-d'))
            ->whereIn('Status', ['Pending', 'Confirmed', 'CheckedIn', 'InProgress'])
            ->count();
        
        return response()->json([
            'success' => true,
            'message' => 'Real dashboard data',
            'doctor' => [
                'id' => $doctorId,
                'name' => $request->user()->FullName
            ],
            'data' => [
                'total_appointments_count' => $total,
                'completed_appointments_count' => $completed,
                'waiting_appointments_count' => $waiting,
                'in_progress_appointments_count' => $inProgress,
                'today_appointments_count' => $today,
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Dashboard error: ' . $e->getMessage());
        
        // Fallback to test data
        return response()->json([
            'success' => true,
            'message' => 'Using test data',
            'data' => [
                'total_appointments_count' => 10,
                'completed_appointments_count' => 3,
                'waiting_appointments_count' => 5,
                'in_progress_appointments_count' => 1,
                'today_appointments_count' => 2,
            ]
        ]);
    }
}
>>>>>>> tung-feature-doctor-dashboard
}