<?php
// Tên file: app/Http/Controllers/Api/Admin/AppointmentManagementController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Specialty;
use Illuminate\Support\Facades\Storage;
use App\Models\Appointment;
use App\Models\DoctorAvailability;

class AppointmentManagementController extends Controller
{
   
    public function index()
    {
        // Lấy tất cả lịch hẹn kèm thông tin Bệnh nhân và Bác sĩ
        // 'patient' và 'doctor.user' là tên các relation trong Model Appointment
        $appointments = Appointment::with(['patient', 'doctor.user', 'service', 'schedule'])->get();

        return response()->json([
            'data' => $appointments
        ]);

    }

    //tra cuu lich lam viec cua 1 bac si trong 1 ngay cu the
    public function checkDoctorSchedule(Request $request)
    {
        $request->validate([
            'DoctorID' => 'required|integer|exists:doctors,DoctorID',
            'Date' => 'required|date_format:Y-m-d',
        ]);
        //query vao bang availability de lay lich lam viec
        $schedule = DoctorAvailability::where('DoctorID', $request->DoctorID)
            ->whereDate('StartTime', $request->Date)
            ->with(['appointment.patient'])
            ->orderBy('StartTime', 'asc')
            ->get();
        return response()->json($schedule);
    }
    
}