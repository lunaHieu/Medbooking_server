<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Lấy lịch làm việc của bác sĩ
     */
    public function getSchedule(Request $request)
    {
        try {
            Log::info('📅 [getSchedule] Request received', [
                'user_id' => auth()->id(),
                'params' => $request->all()
            ]);

            // Lấy ID bác sĩ từ user đang đăng nhập
            $doctorId = null;
            $user = auth()->user();
            
            if ($user->Role === 'BacSi' && $user->doctor) {
                $doctorId = $user->doctor->DoctorID;
            }
            
            if (!$doctorId) {
                Log::warning('⚠️ [getSchedule] Không tìm thấy doctor ID', [
                    'user_id' => $user->UserID,
                    'role' => $user->Role,
                    'has_doctor' => isset($user->doctor)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không phải bác sĩ hoặc chưa có thông tin bác sĩ'
                ], 403);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            Log::info('📅 [getSchedule] Query params', [
                'doctor_id' => $doctorId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Query appointments với eager loading
            $query = Appointment::with([
                'patient' => function($query) {
                    $query->select('UserID', 'FullName', 'PhoneNumber', 'Email', 'DateOfBirth', 'Gender');
                },
                'service' => function($query) {
                    $query->select('ServiceID', 'ServiceName');
                }
            ])->where('DoctorID', $doctorId);

            // Lọc theo khoảng thời gian
            if ($startDate && $endDate) {
                $query->whereBetween('StartTime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else {
                // Mặc định lấy 2 tuần
                $defaultStart = date('Y-m-d', strtotime('-1 week'));
                $defaultEnd = date('Y-m-d', strtotime('+1 week'));
                $query->whereBetween('StartTime', [$defaultStart . ' 00:00:00', $defaultEnd . ' 23:59:59']);
            }

            $appointments = $query->orderBy('StartTime', 'asc')->get();

            Log::info('📅 [getSchedule] Found appointments', [
                'count' => $appointments->count(),
                'doctor_id' => $doctorId
            ]);

            // Transform dữ liệu
            $transformedAppointments = $appointments->map(function($appointment) {
                // Tính tuổi nếu có ngày sinh
                $age = null;
                if ($appointment->patient && $appointment->patient->DateOfBirth) {
                    $birthDate = new \DateTime($appointment->patient->DateOfBirth);
                    $today = new \DateTime();
                    $age = $birthDate->diff($today)->y;
                }

                return [
                    'AppointmentID' => $appointment->AppointmentID,
                    'PatientID' => $appointment->PatientID,
                    'DoctorID' => $appointment->DoctorID,
                    'ServiceID' => $appointment->ServiceID,
                    'SlotID' => $appointment->SlotID,
                    'StartTime' => $appointment->StartTime,
                    'EstimatedDuration' => $appointment->EstimatedDuration,
                    'InitialSymptoms' => $appointment->InitialSymptoms,
                    'Status' => $appointment->Status,
                    'CancellationReason' => $appointment->CancellationReason,
                    'Type' => $appointment->Type,
                    'created_at' => $appointment->created_at,
                    'updated_at' => $appointment->updated_at,
                    
                    // Thông tin bệnh nhân đầy đủ
                    'patient_info' => $appointment->patient ? [
                        'id' => $appointment->patient->UserID,
                        'full_name' => $appointment->patient->FullName,
                        'phone' => $appointment->patient->PhoneNumber,
                        'email' => $appointment->patient->Email,
                        'date_of_birth' => $appointment->patient->DateOfBirth,
                        'age' => $age,
                        'gender' => $appointment->patient->Gender,
                    ] : null,
                    
                    // Thông tin dịch vụ
                    'service_info' => $appointment->service ? [
                        'id' => $appointment->service->ServiceID,
                        'name' => $appointment->service->ServiceName,
                    ] : null,
                ];
            });

            Log::info('✅ [getSchedule] Successfully transformed appointments');

            return response()->json([
                'success' => true,
                'data' => $transformedAppointments,
                'total' => $appointments->count(),
                'message' => 'Lấy lịch hẹn thành công'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [getSchedule] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết lịch hẹn
     */
    public function getAppointmentDetail($id)
    {
        try {
            Log::info('🔍 [getAppointmentDetail] Request for appointment', ['appointment_id' => $id]);

            $appointment = Appointment::with([
                'patient' => function($query) {
                    $query->select('UserID', 'FullName', 'PhoneNumber', 'Email', 
                                 'DateOfBirth', 'Gender', 'Address', 'Avatar');
                },
                'service',
                'doctor' => function($query) {
                    $query->with(['user' => function($q) {
                        $q->select('UserID', 'FullName', 'PhoneNumber');
                    }]);
                },
                'medicalRecord' => function($query) {
                    $query->with('prescriptions');
                }
            ])->find($id);

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy lịch hẹn'
                ], 404);
            }

            // Kiểm tra quyền truy cập (bác sĩ chỉ xem được lịch hẹn của mình)
            $doctorId = auth()->user()->doctor->DoctorID ?? null;
            if ($doctorId && $appointment->DoctorID != $doctorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem lịch hẹn này'
                ], 403);
            }

            // Tính tuổi
            $age = null;
            if ($appointment->patient && $appointment->patient->DateOfBirth) {
                $birthDate = new \DateTime($appointment->patient->DateOfBirth);
                $today = new \DateTime();
                $age = $birthDate->diff($today)->y;
            }

            $response = [
                'success' => true,
                'data' => [
                    'appointment' => [
                        'id' => $appointment->AppointmentID,
                        'patient_id' => $appointment->PatientID,
                        'doctor_id' => $appointment->DoctorID,
                        'start_time' => $appointment->StartTime,
                        'symptoms' => $appointment->InitialSymptoms,
                        'status' => $appointment->Status,
                        'type' => $appointment->Type,
                        'cancellation_reason' => $appointment->CancellationReason,
                        'service_id' => $appointment->ServiceID,
                    ],
                    'patient' => $appointment->patient ? [
                        'id' => $appointment->patient->UserID,
                        'name' => $appointment->patient->FullName,
                        'full_name' => $appointment->patient->FullName,
                        'phone' => $appointment->patient->PhoneNumber,
                        'email' => $appointment->patient->Email,
                        'date_of_birth' => $appointment->patient->DateOfBirth,
                        'age' => $age,
                        'gender' => $appointment->patient->Gender,
                        'address' => $appointment->patient->Address,
                        'avatar' => $appointment->patient->Avatar,
                    ] : null,
                    'service' => $appointment->service,
                    'medical_record' => $appointment->medicalRecord,
                ],
                'message' => 'Lấy chi tiết lịch hẹn thành công'
            ];

            Log::info('✅ [getAppointmentDetail] Success', ['appointment_id' => $id]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('❌ [getAppointmentDetail] Error: ' . $e->getMessage(), [
                'appointment_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }
}