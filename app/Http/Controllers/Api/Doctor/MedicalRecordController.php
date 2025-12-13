<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MedicalRecord;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ExamResult; // Sửa import

class MedicalRecordController extends Controller
{

    public function myMedicalRecords(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctorProfile;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy hồ sơ bác sĩ'
            ], 404);
        }

        $records = MedicalRecord::where('DoctorID', $doctor->DoctorID)
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách bệnh án thành công',
            'data' => $records
        ]);
    }

    public function testData()
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'patientName' => "Trần Thị Lan",
                    'age' => 34,
                    'diagnosis' => "Viêm họng cấp do virus",
                    'treatment' => "Kháng sinh 5 ngày, nghỉ ngơi, uống nhiều nước, hạ sốt khi cần",
                    'prescriptions' => [
                        ['medicine' => "Amoxicillin", 'dosage' => "500mg", 'frequency' => "3 lần/ngày"],
                        ['medicine' => "Paracetamol", 'dosage' => "500mg", 'frequency' => "Khi sốt >38.5°C"]
                    ],
                    'tests' => ["Xét nghiệm máu", "Ngoáy họng", "CRP"],
                    'date' => "2025-04-02",
                    'status' => "completed",
                ],
                [
                    'id' => 2,
                    'patientName' => "Lê Văn Tùng",
                    'age' => 45,
                    'diagnosis' => "Tăng huyết áp độ 2, Rối loạn mỡ máu",
                    'treatment' => "Điều chỉnh lối sống, thuốc hạ áp, theo dõi định kỳ, ăn kiêng",
                    'prescriptions' => [
                        ['medicine' => "Losartan", 'dosage' => "50mg", 'frequency' => "1 lần/ngày"],
                        ['medicine' => "Amlodipine", 'dosage' => "5mg", 'frequency' => "1 lần/ngày"],
                        ['medicine' => "Atorvastatin", 'dosage' => "20mg", 'frequency' => "1 lần/ngày"]
                    ],
                    'tests' => ["Đo huyết áp 24h", "Xét nghiệm máu", "Điện tâm đồ", "Siêu âm tim"],
                    'date' => "2025-03-28",
                    'status' => "completed",
                ]
            ]
        ]);
    }
}