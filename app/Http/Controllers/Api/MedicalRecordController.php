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
        //Validation
        $request->validate([
            'AppointmentID' => 'required|integer|exists:appointments,AppointmentID|unique:medical_records,AppointmentID',
            'Diagnosis' => 'required|string',
            'CurrentSymptoms' => 'required|string',
            'Notes' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // Max 10MB/file
        ]);

        $user = Auth::user();
        $doctor = $user->doctorProfile;

        //Kiểm tra quyền và hồ sơ bác sĩ
        if (!$doctor || $user->Role !== 'BacSi') {
            return response()->json(['message' => 'Bạn không có quyền thực hiện thao tác này'], 403);
        }

        //Kiểm tra lịch hẹn
        $appointment = Appointment::where('AppointmentID', $request->AppointmentID)->first();
        if (!$appointment || $doctor->DoctorID != $appointment->DoctorID) {
            return response()->json(['message' => 'Lịch hẹn không hợp lệ hoặc bạn không được phân công'], 403);
        }

        try {
            DB::beginTransaction();

            //Tạo Bệnh án mới 
            $record = new MedicalRecord();
            $record->AppointmentID = $request->AppointmentID;
            $record->PatientID = $appointment->PatientID;
            $record->DoctorID = $doctor->DoctorID;
            $record->Diagnosis = $request->Diagnosis;
            $record->Notes = $request->Notes;
            $record->CurrentSymptoms = $request->CurrentSymptoms;
            $record->save();

            //Xử lý lưu tệp tin vào bảng exam_results
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Lưu file vào thư mục storage/app/public/medical_results
                    $path = $file->store('medical_results', 'public');

                    // Tạo bản ghi trong bảng exam_results
                    DB::table('exam_results')->insert([
                        'RecordID' => $record->RecordID,
                        'FilePath' => $path,
                        'FileType' => $file->getClientOriginalExtension(), // Phân loại đuôi file
                        'FileDescription' => 'Kết quả đính kèm: ' . $file->getClientOriginalName(),
                        'created_at' => now(),
                    ]);
                }
            }

            //Cập nhật trạng thái lịch hẹn
            $appointment->Status = 'Completed';
            $appointment->save();

            DB::commit();

            return response()->json([
                'message' => 'Lưu bệnh án và kết quả xét nghiệm thành công!',
                'record' => $record
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi máy chủ, không thể lưu dữ liệu.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Tải file kết quả (X-quang, PDF...) cho 1 Bệnh án.
     * Chạy khi gọi POST /api/doctor/medical-records/{id}/upload-result
     */
    public function uploadResult(Request $request, $id)
    {
        //Kiểm tra file gửi lên
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf,dicom|max:10240', // Tối đa 10MB
            'description' => 'nullable|string|max:500'
        ]);

        // Tìm Bệnh án (Medical Record) cha
        $record = MedicalRecord::findOrFail($id);
        $doctor = $request->user()->doctorProfile;

        //Kiểm tra Bác sĩ có phải là người tạo Bệnh án 
        if ($doctor->DoctorID !== $record->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền tải file lên bệnh án này.'], 403);
        }

        //Lấy file từ request
        $file = $request->file('file');

        // Lưu file vào 'storage/app/public/exam_results/'
        $path = $file->store('uploads/exam_results/patient_' . $record->PatientID, 'public');

        // Tạo bản ghi mới trong bảng 'exam_results'
        $examResult = new ExamResult();
        $examResult->RecordID = $record->RecordID;
        $examResult->FilePath = $path;
        $examResult->FileType = $file->getClientMimeType();
        $examResult->FileDescription = $request->input('description');
        $examResult->save();

        return response()->json([
            'message' => 'Tải file kết quả thành công!',
            'result' => $examResult
        ], 201); // 201 Created
    }
    /**
     * Lấy danh sách bệnh án do chính Bác sĩ này tạo.
     * Chạy khi gọi GET /api/doctor/my-medical-records
     */
    public function myMedicalRecords(Request $request)
    {
        //Lấy thông tin Bác sĩ
        $doctor = $request->user()->doctorProfile;

        //Lấy tất cả MedicalRecord có 'DoctorID' khớp
        $records = MedicalRecord::where('DoctorID', $doctor->DoctorID)
            ->with('patient')
            ->orderBy('created_at', 'desc') // Mới nhất lên đầu
            ->get();

        return response()->json($records, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * Lấy toàn bộ lịch sử bệnh án của 1 Bệnh nhân.
     * Chạy khi gọi GET /api/doctor/patient-history/{patientId}
     */
    public function getPatientHistory(Request $request, $patientId)
    {
        // Lấy tất cả MedicalRecord có 'PatientID' khớp
        $records = MedicalRecord::where('PatientID', $patientId)
            // Lấy cả Bác sĩ (và tên Bác sĩ) đã khám
            ->with('doctor.user')
            ->orderBy('created_at', 'desc') // Mới nhất lên đầu
            ->get();

        return response()->json($records, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * Cập nhật Bệnh án (chẩn đoán, ghi chú).
     * Chạy khi gọi PUT /api/doctor/medical-records/{id}
     */
    public function update(Request $request, $id)
    {
        // Tìm Bệnh án
        $record = MedicalRecord::findOrFail($id);
        $doctor = $request->user()->doctorProfile;

        // Kiểm tra Bác sĩ có phải là người tạo Bệnh án này 
        if ($doctor->DoctorID !== $record->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền sửa bệnh án này.'], 403);
        }

        //Validate dữ liệu mới
        $request->validate([
            'Diagnosis' => 'required|string', // Chẩn đoán
            'Notes' => 'nullable|string',     // Ghi chú thêm
        ]);

        //Cập nhật và lưu
        $record->Diagnosis = $request->Diagnosis;
        $record->Notes = $request->Notes;
        $record->save();

        //Trả về thông báo thành công
        return response()->json([
            'message' => 'Cập nhật bệnh án thành công!',
            'record' => $record
        ], 200); // 200 OK
    }
    /**
     * Xóa một Bệnh án.
     * Chạy khi gọi DELETE /api/admin/medical-records/{id}
     */
    public function destroy($id)
    {
        $record = MedicalRecord::findOrFail($id);

        // Lưu ý: Migration của 'exam_results' có 'onDelete('cascade')'
        // nên khi xóa Bệnh án, các file kết quả liên quan cũng TỰ ĐỘNG bị xóa
        // khỏi bảng 'exam_results'.

        // Xóa các file vật lý trong Storage
        // foreach ($record->examResults as $result) {
        //     Storage::disk('public')->delete($result->FilePath);
        // }

        $record->delete();

        return response()->json(null, 204); // 204 No Content
    }
    /**
     * Lấy danh sách/Tìm kiếm TẤT CẢ Bệnh án.
     * Chạy khi gọi GET /api/admin/medical-records
     * Ví dụ: /api/admin/medical-records?patient_id=3
     */
    public function index(Request $request)
    {
        $query = MedicalRecord::query()->with(['patient', 'doctor.user', 'appointment']);

        // Lọc (Filter) theo ID Bệnh nhân
        if ($request->has('patient_id')) {
            $query->where('PatientID', $request->input('patient_id'));
        }

        // Lọc (Filter) theo ID Bác sĩ
        if ($request->has('doctor_id')) {
            $query->where('DoctorID', $request->input('doctor_id'));
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        return response()->json($records, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lấy chi tiết 1 Bệnh án.
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