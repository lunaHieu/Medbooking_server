<?php
// Tên file: app/Http/Controllers/Api/AppointmentController.php (Bản Sạch 100%)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Cho 'Auth'
use App\Models\Appointment;          // Cho Model 'Appointment'
use App\Models\DoctorAvailability; // Cho Model 'DoctorAvailability'
use Illuminate\Support\Facades\DB;   // Cho 'DB' (Transaction)
use Illuminate\Support\Facades\Storage;
class AppointmentController extends Controller
{
    /**
     * Bệnh nhân lấy lịch hẹn của mình
     */
    public function myAppointments(Request $request)
    {
        $user = $request->user();

        $appointments = $user->appointmentsAsPatient()
                            ->orderBy('StartTime', 'desc')
                            ->get();

        return response()->json($appointments, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Bệnh nhân tạo lịch hẹn mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'SlotID' => 'required|integer|exists:doctor_availability,SlotID',
            'InitialSymptoms' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240' // Tối đa 10MB
        ]);

        $user = $request->user();

        try {
            DB::beginTransaction();

            $slot = DoctorAvailability::where('SlotID', $request->SlotID)
                                      ->lockForUpdate()
                                      ->first();

            if ($slot->Status == 'Booked') {
                DB::rollBack();
                return response()->json([
                    'message' => 'Lỗi: Slot này đã có người khác đặt.'
                ], 409);
            }

            $slot->Status = 'Booked';
            $slot->save();

            $filePath = null;
            if ($request->hasFile('file')) {
                // Lưu file vào 'storage/app/public/uploads/attachments/patient_...'
                $path = $request->file('file')->store('uploads/attachments/patient_' . $user->UserID, 'public');
                $filePath = $path;
            }

            $appointment = new Appointment();
            $appointment->PatientID = $user->UserID;
            $appointment->DoctorID = $slot->DoctorID;
            $appointment->SlotID = $slot->SlotID;
            $appointment->StartTime = $slot->StartTime;
            $appointment->Status = 'Pending';
            $appointment->InitialSymptoms = $request->InitialSymptoms;
            $appointment->save();

            DB::commit();

            return response()->json([
                'message' => 'Đặt lịch thành công!',
                'appointment' => $appointment
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi máy chủ, không thể đặt lịch.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bệnh nhân tự hủy lịch hẹn
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id); 

        // === PHÂN QUYỀN (Authorization) ===
        if ($user->UserID !== $appointment->PatientID) {
            return response()->json(['message' => 'Bạn không có quyền hủy lịch hẹn này.'], 403);
        }

        if ($appointment->Status === 'Completed' || $appointment->Status === 'Cancelled') {
            return response()->json([
                'message' => 'Không thể hủy lịch hẹn đã hoàn thành hoặc đã bị hủy.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $appointment->Status = 'Cancelled';
            $appointment->CancellationReason = 'Bệnh nhân tự hủy';
            $appointment->save();

            if ($appointment->SlotID) {
                $slot = DoctorAvailability::find($appointment->SlotID);
                if ($slot) {
                    $slot->Status = 'Available';
                    $slot->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Hủy lịch hẹn thành công.',
                'appointment' => $appointment
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi máy chủ, không thể hủy lịch.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bác sĩ xem các lịch hẹn đã đặt của mình
     */
    public function doctorSchedule(Request $request)
    {
        // === ĐÂY LÀ DÒNG LỖI (SỬA LẠI THỨ TỰ) ===
        // 1. Lấy thông tin Bác sĩ (Doctor Profile) của User đang đăng nhập
        $doctor = $request->user()->doctorProfile;

        // 2. Dùng biến $doctor (đã được định nghĩa) để lấy lịch hẹn
        $appointments = $doctor->appointments()
           // ->whereIn('Status', ['Confirmed', 'Completed'])
            ->with('patient') // Eager Load thông tin Bệnh nhân
            ->orderBy('StartTime', 'asc')
            ->get();

        return response()->json($appointments, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * HÀM MỚI (Staff): Xác nhận lịch hẹn.
     */
    public function confirmAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        if ($appointment->Status !== 'Pending') {
            return response()->json(['message' => 'Lịch hẹn này đã được xử lý (không ở trạng thái "Pending").'], 422);
        }
        $appointment->Status = 'Confirmed';
        $appointment->save();
        return response()->json(['message' => 'Xác nhận lịch hẹn thành công!', 'appointment' => $appointment], 200);
    }

    /**
     * HÀM MỚI (Staff): Hủy lịch hẹn.
     */
    public function staffCancelAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $reason = $request->input('reason', 'Nhân viên hủy lịch');

        try {
            DB::beginTransaction();
            $appointment->Status = 'Cancelled';
            $appointment->CancellationReason = $reason;
            $appointment->save();

            if ($appointment->SlotID) {
                $slot = DoctorAvailability::find($appointment->SlotID);
                if ($slot && $slot->Status === 'Booked') {
                    $slot->Status = 'Available';
                    $slot->save();
                }
            }
            DB::commit();
            return response()->json(['message' => 'Hủy lịch hẹn thành công (bởi Staff).', 'appointment' => $appointment], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi máy chủ.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * HÀM MỚI (Staff): Tạo lịch hẹn (thay mặt Bệnh nhân).
     */
    public function staffCreateAppointment(Request $request)
    {
        $request->validate([
            'SlotID' => 'required|integer|exists:doctor_availability,SlotID',
            'PatientID' => 'required|integer|exists:users,UserID', // <-- Staff phải chọn Bệnh nhân
            'InitialSymptoms' => 'nullable|string',
            'Status' => 'required|in:Pending,Confirmed' // Staff có thể xác nhận ngay
        ]);

        try {
            DB::beginTransaction();
            $slot = DoctorAvailability::where('SlotID', $request->SlotID)->lockForUpdate()->first();

            if ($slot->Status == 'Booked') {
                DB::rollBack();
                return response()->json(['message' => 'Lỗi: Slot này đã có người khác đặt.'], 409);
            }

            $slot->Status = 'Booked';
            $slot->save();

            $appointment = new Appointment();
            $appointment->PatientID = $request->PatientID; // <-- Dùng PatientID từ request
            $appointment->DoctorID = $slot->DoctorID;
            $appointment->SlotID = $slot->SlotID;
            $appointment->StartTime = $slot->StartTime;
            $appointment->Status = $request->Status; // <-- Dùng Status từ request
            $appointment->InitialSymptoms = $request->InitialSymptoms;
            $appointment->save();

            DB::commit();
            return response()->json(['message' => 'Staff tạo lịch hẹn thành công!', 'appointment' => $appointment], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi máy chủ.', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * HÀM MỚI (Staff): Cập nhật chi tiết lịch hẹn.
     */
    public function staffUpdateAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $request->validate([
            'SlotID' => 'required|integer|exists:doctor_availability,SlotID',
            'PatientID' => 'required|integer|exists:users,UserID',
            'InitialSymptoms' => 'nullable|string',
            'Status' => 'required|in:Pending,Confirmed,Completed,Cancelled'
        ]);

        // (Logic này giả định SlotID không thay đổi, chỉ cập nhật chi tiết)
        // (Nếu đổi SlotID, logic sẽ phức tạp hơn - cần mở slot cũ, khóa slot mới)
        $appointment->update($request->all());

        return response()->json(['message' => 'Cập nhật lịch hẹn thành công!', 'appointment' => $appointment], 200);
    }
   /* HÀM MỚI (Staff): Check-in cho Bệnh nhân (chuyển từ 'Confirmed' -> 'CheckedIn').
     * Chạy khi gọi PATCH /api/staff/appointments/{id}/check-in
     */
    public function checkInAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        // Logic nghiệp vụ: Chỉ cho phép check-in 
        // nếu lịch hẹn đã được "Xác nhận" (Confirmed)
        if ($appointment->Status !== 'Confirmed') {
            return response()->json([
                'message' => 'Không thể check-in. Lịch hẹn này không ở trạng thái "Đã xác nhận".'
            ], 422); // 422 Unprocessable Entity
        }

        // Cập nhật trạng thái
        $appointment->Status = 'CheckedIn';
        $appointment->save();

        // (Logic nâng cao: Có thể gửi thông báo cho Bác sĩ "Bệnh nhân A đã đến")

        return response()->json([
            'message' => 'Check-in cho bệnh nhân thành công!',
            'appointment' => $appointment
        ], 200);
    }
    /**
     * HÀM MỚI (Doctor): Lấy hàng đợi bệnh nhân đã check-in
     * Chạy khi gọi GET /api/doctor/queue
     */
    public function getDoctorQueue(Request $request)
    {
        // 1. Lấy thông tin Bác sĩ (Doctor Profile) của User đang đăng nhập
        $doctor = $request->user()->doctorProfile;

        // 2. Lấy các lịch hẹn có Status là 'CheckedIn'
        // VÀ của chính bác sĩ này
        $queue = $doctor->appointments() // Lấy lịch của tôi
            ->where('Status', 'CheckedIn')
            ->with('patient') // Lấy thông tin bệnh nhân
            ->orderBy('StartTime', 'asc') // Ưu tiên người đến sớm
            ->get();

        // 3. Trả về JSON
        return response()->json($queue, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * HÀM MỚI (Doctor): Lấy chi tiết 1 lịch hẹn.
     * Chạy khi gọi GET /api/doctor/appointments/{id}
     */
    public function doctorShowAppointment(Request $request, $id)
    {
        $doctor = $request->user()->doctorProfile;
        
        // Tìm lịch hẹn VÀ Eager Load 'patient' và 'medicalRecord'
        $appointment = Appointment::with('patient', 'medicalRecord')
                                    ->findOrFail($id);

        // 1. === LOGIC PHÂN QUYỀN (AUTHORIZATION) ===
        // Bác sĩ có phải là người khám lịch hẹn này không?
        if ($doctor->DoctorID !== $appointment->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền xem lịch hẹn này.'], 403);
        }

        // 2. Trả về chi tiết
        return response()->json($appointment, 200, [], JSON_UNESCAPED_UNICODE);
    }


}