<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ bác sĩ'], 404);
        }

        $appointments = $doctor->appointments()->get();

        $totalAppointments = $appointments->whereIn('Status', ['Pending', 'Confirmed', 'CheckedIn', 'Completed'])->count();
        $completedAppointments = $appointments->where('Status', 'Completed')->count();
        $waitingAppointments = $appointments->whereIn('Status', ['Pending', 'CheckedIn'])->count();

        return response()->json([
            'total_appointments_count' => $totalAppointments,
            'completed_appointments_count' => $completedAppointments,
            'waiting_appointments_count' => $waitingAppointments,
        ]);
    }

    public function testData()
{
    return response()->json([
        'total_appointments_count' => 10,
        'completed_appointments_count' => 5,
        'waiting_appointments_count' => 3,
    ]);
}

}