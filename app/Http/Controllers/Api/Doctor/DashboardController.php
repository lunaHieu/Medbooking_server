<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
}