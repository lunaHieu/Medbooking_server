<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $request->user()->doctorProfile;

        if (!$doctor) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ bác sĩ'], 404);
        }

        // Lấy lịch khám của bác sĩ (có thể thêm điều kiện ngày nếu cần)
        $appointments = $doctor->appointments()
            ->with(['patient', 'service'])
            ->whereIn('Status', ['Pending', 'Confirmed', 'CheckedIn', 'Completed'])
            ->orderBy('StartTime')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }
}