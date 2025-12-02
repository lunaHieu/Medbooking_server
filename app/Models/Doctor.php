<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\Appointment; // ← THÊM DÒNG NÀY LÀ HẾT LỖI 500!!!

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user || !$user->doctorProfile) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ bác sĩ'], 404);
        }

        $doctor = $user->doctorProfile;

        $appointments = $doctor->appointments()
            ->whereIn('Status', ['Pending', 'Confirmed', 'CheckedIn', 'Completed'])
            ->get();

        return response()->json([
            'total_appointments_count' => $appointments->count(),
            'completed_appointments_count' => $appointments->where('Status', 'Completed')->count(),
            'waiting_appointments_count' => $appointments->whereIn('Status', ['Pending', 'CheckedIn'])->count(),
        ]);
    }
}