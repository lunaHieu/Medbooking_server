<?php
namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\Appointment;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctorProfile;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy hồ sơ bác sĩ'
            ], 404);
        }

        $queue = $doctor->appointments()
            ->with(['patient', 'service'])
            ->whereIn('Status', ['CheckedIn', 'Confirmed'])
            ->whereDate('StartTime', today())
            ->orderBy('StartTime')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách hàng đợi thành công',
            'data' => $queue
        ]);
    }

    public function testData()
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'name' => "Trần Thị Lan",
                    'age' => 34,
                    'gender' => "female",
                    'phone' => "0901234567",
                    'symptoms' => "Ho, sốt 3 ngày, đau họng, mệt mỏi",
                    'appointmentTime' => "09:00",
                    'status' => "checked_in",
                    'checkInTime' => "08:50",
                    'priority' => "high",
                    'allergies' => ["Penicillin", "Aspirin"],
                    'medicalHistory' => ["Tiểu đường type 2", "Cao huyết áp"],
                ],
                [
                    'id' => 2,
                    'name' => "Lê Văn Tùng",
                    'age' => 45,
                    'gender' => "male",
                    'phone' => "0912345678",
                    'symptoms' => "Đau đầu, chóng mặt, buồn nôn",
                    'appointmentTime' => "09:30",
                    'status' => "checked_in",
                    'checkInTime' => "09:15",
                    'priority' => "medium",
                    'allergies' => ["Paracetamol"],
                    'medicalHistory' => ["Cao huyết áp", "Rối loạn mỡ máu"],
                ]
            ]
        ]);
    }
}