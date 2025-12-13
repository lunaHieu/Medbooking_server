<?php
// Tên file: app/Http/Controllers/Api/MedicalRecordController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MedicalRecord; // <-- Thêm
use App\Models\Appointment;   // <-- Thêm
use Illuminate\Support\Facades\Auth; // <-- Thêm
use Illuminate\Support\Facades\DB;   // <-- Thêm
use App\Illuminate\ExamResult;
use Illuminate\Support\Facades\Storage;

class MedicalRecordController extends Controller
{
    /**
     * Bác sĩ tạo một Hồ sơ Bệnh án (Medical Record) mới.
     * Chạy khi gọi POST /api/doctor/medical-records
     */
    public function store(Request $request)
{
    $request->validate([
        'AppointmentID' => 'required|integer|exists:appointments,AppointmentID|unique:medical_records,AppointmentID,NULL,RecordID',
        'Diagnosis' => 'required|string',
        'Notes' => 'nullable|string',
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Chưa đăng nhập'], 401);
    }

    if ($user->Role !== 'BacSi') {
        return response()->json(['message' => 'Bạn không phải bác sĩ'], 403);
    }

    $doctor = $user->doctorProfile;

    if (!$doctor) {
        return response()->json([
            'message' => 'Không tìm thấy hồ sơ bác sĩ. Vui lòng kiểm tra bảng doctors có dòng UserID = ' . $user->UserID . ' chưa?'
        ], 403);
    }

    $appointment = Appointment::where('AppointmentID', $request->AppointmentID)->first();
    if (!$appointment) {
        return response()->json(['message' => 'Lịch hẹn không tồn tại'], 404);
    }

    if ($doctor->DoctorID != $appointment->DoctorID) {
        return response()->json(['message' => 'Bạn không phải bác sĩ được phân công'], 403);
    }

        // 2. Lấy thông tin Bác sĩ (Doctor Profile)
        $doctor = Auth::user()->doctorProfile;

        // 3. Tìm Lịch hẹn (Appointment) tương ứng
        $appointment = Appointment::where('AppointmentID', $request->AppointmentID)->first();

if (!$appointment) {
    return response()->json([
        'message' => 'Lịch hẹn không tồn tại hoặc đã bị xóa. AppointmentID: ' . $request->AppointmentID
    ], 404);
}
        // 4. === LOGIC PHÂN QUYỀN (AUTHORIZATION) ===
        // Bác sĩ có phải là người khám lịch hẹn này không?
       if ($doctor->DoctorID != $appointment->DoctorID) {
    return response()->json([
        'message' => 'Bạn không phải bác sĩ được phân công cho lịch hẹn này.'
    ], 403);
}

        // 5. Bắt đầu Transaction
        try {
            DB::beginTransaction();

            // 6. Tạo Bệnh án mới
            $record = new MedicalRecord();
            $record->AppointmentID = $request->AppointmentID;
            $record->PatientID = $appointment->PatientID; // Lấy từ Lịch hẹn
            $record->DoctorID = $doctor->DoctorID;       // Lấy từ Bác sĩ
            $record->Diagnosis = $request->Diagnosis;
            $record->Notes = $request->Notes;
            $record->save();

            // 7. Cập nhật Status của Lịch hẹn thành 'Completed' (Đã khám)
            $appointment->Status = 'Completed';
            $appointment->save();

            // 8. Hoàn tất
            DB::commit();

            return response()->json([
                'message' => 'Tạo hồ sơ bệnh án thành công!',
                'record' => $record
            ], 201); // 201 Created

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi máy chủ, không thể tạo hồ sơ.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * HÀM MỚI (Doctor): Tải file kết quả (X-quang, PDF...) cho 1 Bệnh án.
     * Chạy khi gọi POST /api/doctor/medical-records/{id}/upload-result
     */
    public function uploadResult(Request $request, $id)
    {
        // 1. Validate (Kiểm tra) file gửi lên
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf,dicom|max:10240', // Tối đa 10MB
            'description' => 'nullable|string|max:500' // Mô tả (ví dụ: "Kết quả X-quang phổi")
        ]);

        // 2. Tìm Bệnh án (Medical Record) cha
        $record = MedicalRecord::findOrFail($id);
        $doctor = $request->user()->doctorProfile;

        // 3. === LOGIC PHÂN QUYỀN (AUTHORIZATION) ===
        // Bác sĩ có phải là người tạo Bệnh án này không?
        if ($doctor->DoctorID !== $record->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền tải file lên bệnh án này.'], 403);
        }

        // 4. Lấy file từ request
        $file = $request->file('file');

        // 5. Lưu file vào 'storage/app/public/exam_results/'
        // Chúng ta lưu trong một thư mục con theo PatientID để dễ quản lý
        $path = $file->store('uploads/exam_results/patient_' . $record->PatientID, 'public');

        // 6. Tạo bản ghi mới trong bảng 'exam_results'
        $examResult = new ExamResult();
        $examResult->RecordID = $record->RecordID; // Liên kết với Bệnh án
        $examResult->FilePath = $path; // Đường dẫn tương đối
        $examResult->FileType = $file->getClientMimeType(); // Lấy loại file (ví dụ: 'image/jpeg')
        $examResult->FileDescription = $request->input('description');
        $examResult->save();

        // 7. Trả về thông tin file đã tải lên
        return response()->json([
            'message' => 'Tải file kết quả thành công!',
            'result' => $examResult
        ], 201); // 201 Created
    }
    /**
     * HÀM MỚI (Doctor): Lấy danh sách bệnh án do chính Bác sĩ này tạo.
     * Chạy khi gọi GET /api/doctor/my-medical-records
     */
    public function myMedicalRecords(Request $request)
    {
        // 1. Lấy thông tin Bác sĩ
        $doctor = $request->user()->doctorProfile;

        // 2. Lấy tất cả MedicalRecord có 'DoctorID' khớp
        $records = MedicalRecord::where('DoctorID', $doctor->DoctorID)
            // Eager Load 'patient' để lấy Tên Bệnh nhân
            ->with('patient')
            ->orderBy('created_at', 'desc') // Mới nhất lên đầu
            ->get();

        // 3. Trả về JSON
        return response()->json($records, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * HÀM MỚI (Doctor): Lấy toàn bộ lịch sử bệnh án của 1 Bệnh nhân.
     * Chạy khi gọi GET /api/doctor/patient-history/{patientId}
     */
    public function getPatientHistory(Request $request, $patientId)
    {
        // 1. Lấy tất cả MedicalRecord có 'PatientID' khớp
        $records = MedicalRecord::where('PatientID', $patientId)
            // Lấy cả Bác sĩ (và tên Bác sĩ) đã khám
            ->with('doctor.user') 
            ->orderBy('created_at', 'desc') // Mới nhất lên đầu
            ->get();

        // (Lưu ý: Chúng ta không cần Phân quyền ở đây,
        // vì Bác sĩ được phép xem lịch sử của bất kỳ bệnh nhân nào
        // để phục vụ việc chẩn đoán.)

        // 2. Trả về JSON
        return response()->json($records, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * HÀM MỚI (Doctor): Cập nhật Bệnh án (chẩn đoán, ghi chú).
     * Chạy khi gọi PUT /api/doctor/medical-records/{id}
     */
    public function update(Request $request, $id)
    {
        // 1. Tìm Bệnh án
        $record = MedicalRecord::findOrFail($id);
        $doctor = $request->user()->doctorProfile;

        // 2. === LOGIC PHÂN QUYỀN (AUTHORIZATION) ===
        // Bác sĩ có phải là người tạo Bệnh án này không?
        if ($doctor->DoctorID !== $record->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền sửa bệnh án này.'], 403);
        }

        // 3. Validate (Kiểm tra) dữ liệu mới
        $request->validate([
            'Diagnosis' => 'required|string', // Chẩn đoán
            'Notes' => 'nullable|string',     // Ghi chú thêm
        ]);

        // 4. Cập nhật và lưu
        $record->Diagnosis = $request->Diagnosis;
        $record->Notes = $request->Notes;
        $record->save();

        // 5. Trả về thông báo thành công
        return response()->json([
            'message' => 'Cập nhật bệnh án thành công!',
            'record' => $record
        ], 200); // 200 OK
    }
    /**
     * HÀM MỚI (Admin): Xóa một Bệnh án.
     * Chạy khi gọi DELETE /api/admin/medical-records/{id}
     */
    public function destroy($id)
    {
        $record = MedicalRecord::findOrFail($id);

        // Logic (chúng ta giả định Admin có quyền xóa mọi thứ)
        // Lưu ý: Migration của 'exam_results' có 'onDelete('cascade')'
        // nên khi xóa Bệnh án, các file kết quả liên quan cũng TỰ ĐỘNG bị xóa
        // khỏi bảng 'exam_results'.
        
        // (Logic nâng cao: Xóa các file vật lý trong Storage)
        // foreach ($record->examResults as $result) {
        //     Storage::disk('public')->delete($result->FilePath);
        // }
        
        $record->delete();

        return response()->json(null, 204); // 204 No Content
    }
    /**
     * HÀM MỚI (Admin/Staff): Lấy danh sách/Tìm kiếm TẤT CẢ Bệnh án.
     * Chạy khi gọi GET /api/admin/medical-records
     * Ví dụ: /api/admin/medical-records?patient_id=3
     */
    public function index(Request $request)
    {
        $query = MedicalRecord::query()->with(['patient', 'doctor.user']);

        // 1. Lọc (Filter) theo ID Bệnh nhân
        if ($request->has('patient_id')) {
            $query->where('PatientID', $request->input('patient_id'));
        }
        
        // 2. Lọc (Filter) theo ID Bác sĩ
        if ($request->has('doctor_id')) {
            $query->where('DoctorID', $request->input('doctor_id'));
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        return response()->json($records, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * HÀM MỚI (Admin/Staff): Lấy chi tiết 1 Bệnh án.
     * Chạy khi gọi GET /api/admin/medical-records/{id}
     */
    public function show($id)
    {
        // Eager Load tất cả các mối quan hệ liên quan
        $record = MedicalRecord::with([
                                'patient', 
                                'doctor.user', 
                                'appointment',
                                'examResults' // Lấy cả các file kết quả
                            ])
                            ->findOrFail($id);
                            
        return response()->json($record, 200, [], JSON_UNESCAPED_UNICODE);
    }
}