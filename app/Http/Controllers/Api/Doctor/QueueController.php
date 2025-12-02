<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $request->user()->doctorProfile;

        if (!$doctor) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ bác sĩ'], 404);
        }

        // Lấy danh sách bệnh nhân đang chờ khám (CheckedIn hoặc Confirmed)
        $queue = $doctor->appointments()
            ->with(['patient', 'service'])
            ->whereIn('Status', ['CheckedIn', 'Confirmed'])
            ->whereDate('StartTime', today())
            ->orderBy('StartTime')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $queue
        ]);
    }
}