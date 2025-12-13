<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;

class ScheduleController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();
    if (!$user || $user->Role !== 'BacSi') {
        return response()->json(['message' => 'Không có quyền'], 403);
    }

    $doctor = \DB::table('doctors')
        ->where('UserID', $user->UserID)
        ->first();

    if (!$doctor) {
        return response()->json(['message' => 'Không tìm thấy hồ sơ bác sĩ'], 404);
    }

    $query = Appointment::where('DoctorID', $doctor->DoctorID)
        ->with(['patient' => fn($q) => $q->select('UserID','FullName','PhoneNumber')]);

    // LỌC THEO START_DATE & END_DATE (KHỚP VỚI FRONTEND)
    if ($request->filled('start_date')) {
        $query->whereDate('StartTime', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('StartTime', '<=', $request->end_date);
    }

    $appointments = $query->orderBy('StartTime','asc')->get();

    return response()->json([
        'success' => true,
        'total' => $appointments->count(),
        'data' => $appointments
    ]);
}
}