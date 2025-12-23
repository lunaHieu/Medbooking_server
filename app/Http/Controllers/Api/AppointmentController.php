<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Feedback;
use App\Models\MedicalRecord;
use App\Models\Doctor;
class AppointmentController extends Controller
{
    /**
     * Bệnh nhân lấy lịch hẹn của mình
     */
    public function myAppointments(Request $request)
    {
        $user = $request->user();
        $appointments = $user->appointmentsAsPatient()
            ->with([
                'doctor.user',
                'doctor.specialty',
                'medicalRecord.examResults',
                'medicalRecord.doctor.user',
            ])
            ->orderBy('StartTime', 'desc')
            ->get();

        return response()->json($appointments, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Bác sĩ tạo lịch Tái khám cho bệnh nhân
     * POST /api/doctor/appointments/follow-up
     */
    public function createFollowUp(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctorProfile; // Giả sử User model có quan hệ doctorProfile

        if (!$doctor) {
            return response()->json(['message' => 'Chỉ bác sĩ mới được thực hiện chức năng này.'], 403);
        }

        $request->validate([
            'PatientID' => 'required|integer|exists:users,UserID',
            'SlotID' => 'required|integer|exists:doctor_availability,SlotID',
            'ServiceID' => 'required|integer|exists:services,ServiceID',
            'Note' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Kiểm tra Slot và Khóa Slot (Chỉ lấy slot của chính bác sĩ này)
            $slot = DoctorAvailability::where('SlotID', $request->SlotID)
                ->where('DoctorID', $doctor->DoctorID)
                ->lockForUpdate()
                ->first();

            if (!$slot || $slot->Status !== 'Available') {
                DB::rollBack();
                return response()->json(['message' => 'Khung giờ này không khả dụng hoặc không phải của bạn.'], 409);
            }

            $slot->Status = 'Booked';
            $slot->save();

            // Tạo lịch hẹn
            $appointment = new Appointment();
            $appointment->PatientID = $request->PatientID;
            $appointment->DoctorID = $doctor->DoctorID;
            $appointment->SlotID = $slot->SlotID;
            $appointment->ServiceID = $request->ServiceID;
            $appointment->StartTime = $slot->StartTime;
            $appointment->Status = 'Confirmed'; // Tái khám thì xác nhận luôn
            $appointment->InitialSymptoms = "Tái khám. Ghi chú: " . $request->Note;
            $appointment->Type = 'FollowUp';
            $appointment->save();

            DB::commit();

            return response()->json([
                'message' => 'Đã đặt lịch tái khám thành công!',
                'appointment' => $appointment
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi tạo lịch tái khám.', 'error' => $e->getMessage()], 500);
        }
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
            $appointment->Type = 'New';
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
     * Lấy danh sách các bác sĩ đã từng khám cho bệnh nhân hiện tại
     * GET /api/my-doctors
     */
    public function getMyDoctors(Request $request)
    {
        $user = $request->user();

        // Lấy danh sách ID các bác sĩ đã từng khám (Status = Completed)
        $doctorIds = Appointment::where('PatientID', $user->UserID)
            ->where('Status', 'Completed')
            ->pluck('DoctorID')
            ->unique();

        //Truy vấn thông tin chi tiết các bác sĩ đó
        $doctors = Doctor::whereIn('DoctorID', $doctorIds)
            ->with(['user', 'specialty'])
            ->get();

        return response()->json($doctors, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * Bệnh nhân tự hủy lịch hẹn
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

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
    public function getSchedule(Request $request)
    {
        // Lấy thông tin Bác sĩ (Doctor Profile) của User đang đăng nhập
        $doctor = $request->user()->doctorProfile;

        $appointments = $doctor->appointments()
            ->whereIn('Status', ['Confirmed', 'Completed', 'CheckedIn'])
            ->with('patient') // Eager Load thông tin Bệnh nhân
            ->orderBy('StartTime', 'asc')
            ->get();

        return response()->json($appointments, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * Staff: Xác nhận lịch hẹn.
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
     * Staff: Hủy lịch hẹn.
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
     * Staff: Tạo lịch hẹn (thay mặt Bệnh nhân).
     */
    public function staffCreateAppointment(Request $request)
    {
        $request->validate([
            'SlotID' => 'required|integer|exists:doctor_availability,SlotID',
            'PatientID' => 'required|integer|exists:users,UserID',
            'InitialSymptoms' => 'nullable|string',
            'Status' => 'required|in:Pending,Confirmed'
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
            $appointment->PatientID = $request->PatientID;
            $appointment->DoctorID = $slot->DoctorID;
            $appointment->SlotID = $slot->SlotID;
            $appointment->StartTime = $slot->StartTime;
            $appointment->Status = $request->Status;
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
     * Cập nhật chi tiết lịch hẹn.
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

        $appointment->update($request->all());

        return response()->json(['message' => 'Cập nhật lịch hẹn thành công!', 'appointment' => $appointment], 200);
    }
    /* Check-in cho Bệnh nhân (chuyển từ 'Confirmed' -> 'CheckedIn').
     * Chạy khi gọi PATCH /api/staff/appointments/{id}/check-in
     */
    public function checkInAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        // Chỉ cho phép check-in 
        // nếu lịch hẹn đã được "Xác nhận" (Confirmed)
        if ($appointment->Status !== 'Confirmed') {
            return response()->json([
                'message' => 'Không thể check-in. Lịch hẹn này không ở trạng thái "Đã xác nhận".'
            ], 422); // 422 Unprocessable Entity
        }

        // Cập nhật trạng thái
        $appointment->Status = 'CheckedIn';
        $appointment->save();

        // Có thể gửi thông báo cho Bác sĩ "Bệnh nhân A đã đến"

        return response()->json([
            'message' => 'Check-in cho bệnh nhân thành công!',
            'appointment' => $appointment
        ], 200);
    }
    /**
     * Lấy hàng đợi bệnh nhân đã check-in
     * Chạy khi gọi GET /api/doctor/queue
     */
    public function getDoctorQueue(Request $request)
    {
        // Lấy thông tin Bác sĩ (Doctor Profile) của User đang đăng nhập
        $doctor = $request->user()->doctorProfile;

        // Lấy các lịch hẹn có Status là 'CheckedIn'
        // VÀ của chính bác sĩ này
        $queue = $doctor->appointments()
            ->where('Status', 'CheckedIn')
            ->with('patient')
            ->orderBy('StartTime', 'asc')
            ->get();

        return response()->json($queue, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function doctorShowAppointment(Request $request, $id)
    {
        $doctor = $request->user()->doctorProfile;

        $appointment = Appointment::with('patient', 'medicalRecord')
            ->findOrFail($id);

        // Kiểm tra Bác sĩ có phải là người khám lịch hẹn này 
        if ($doctor->DoctorID !== $appointment->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền xem lịch hẹn này.'], 403);
        }

        // Trả về chi tiết
        return response()->json($appointment, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function getPendingAppointments(Request $request)
    {

        $appointments = Appointment::with([
            'patient',
            'doctor.user',
            'doctor.specialty'
        ])
            ->where('Status', 'Pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }
    /**
     * Bệnh nhân gửi đánh giá sau khi khám
     */
    public function submitFeedback(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

        // Kiểm tra quyền sở hữu
        if ($user->UserID !== $appointment->PatientID) {
            return response()->json(['message' => 'Bạn không có quyền đánh giá lịch hẹn này.'], 403);
        }

        // Chỉ được đánh giá khi đã "Hoàn thành" (Completed)
        if ($appointment->Status !== 'Completed') {
            return response()->json(['message' => 'Chỉ có thể đánh giá sau khi đã khám xong.'], 422);
        }

        // Kiểm tra xem đã đánh giá chưa (tránh spam)
        $existingFeedback = Feedback::where('AppointmentID', $id)->first();
        if ($existingFeedback) {
            return response()->json(['message' => 'Bạn đã đánh giá lịch hẹn này rồi.'], 422);
        }

        // Validate
        $request->validate([
            'Rating' => 'required|integer|min:1|max:5',
            'Comment' => 'nullable|string|max:500'
        ]);

        // Lưu Feedback
        $feedback = new Feedback();
        $feedback->PatientID = $user->UserID;
        $feedback->AppointmentID = $id;
        $feedback->TargetType = 'Doctor';
        $feedback->TargetID = $appointment->DoctorID;
        $feedback->Rating = $request->Rating;
        $feedback->Comment = $request->Comment;
        $feedback->save();

        return response()->json(['message' => 'Gửi đánh giá thành công!', 'feedback' => $feedback], 201);
    }
    public function submitSystemFeedback(Request $request)
    {
        $request->validate([
            'Rating' => 'required|integer|min:1|max:5',
            'Comment' => 'nullable|string'
        ]);

        $feedback = new Feedback();
        $feedback->PatientID = $request->user()->UserID;
        $feedback->AppointmentID = null;
        $feedback->TargetType = 'System';
        $feedback->TargetID = 0;
        $feedback->Rating = $request->Rating;
        $feedback->Comment = $request->Comment;
        $feedback->save();

        return response()->json(['message' => 'Cảm ơn bạn đã góp ý cho hệ thống!']);
    }
    /**
     * Cập nhật trạng thái lịch hẹn(CheckedIn -> InProgress -> Completed)
     */
    public function updateStatus(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy cuộc hẹn']);
        }

        $newStatus = $request->input('status');

        $appointment->Status = $newStatus;

        if ($newStatus === 'Completed') {
            $appointment->ActualEndTime = now();
        }

        $appointment->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $appointment
        ]);
    }
}