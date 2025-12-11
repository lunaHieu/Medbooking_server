<?php
// Tên file: app/Http/Controllers/Api/DoctorAvailabilityController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DoctorAvailability; // <-- Thêm
use Illuminate\Support\Facades\Auth; // <-- Thêm

class DoctorAvailabilityController extends Controller
{
    /**
     * Bác sĩ tạo một (hoặc nhiều) slot thời gian trống mới.
     * Chạy khi gọi POST /api/doctor/availability
     */
    public function store(Request $request)
    {
        // 1. Validate (Kiểm tra) dữ liệu Bác sĩ gửi lên
        $request->validate([
            'StartTime' => 'required|date|after:now', // Phải là ngày giờ, và ở tương lai
            'EndTime' => 'required|date|after:StartTime', // Phải sau StartTime
        ]);

        // 2. Lấy thông tin Bác sĩ (Doctor Profile)
        // Auth::user() là User (Bác sĩ A)
        // ->doctorProfile là Model Doctor (ID: 1)
        $doctor = Auth::user()->doctorProfile;

        // 3. Tạo Slot mới
        $slot = new DoctorAvailability();
        $slot->DoctorID = $doctor->DoctorID; // Gán ID Bác sĩ
        $slot->StartTime = $request->StartTime;
        $slot->EndTime = $request->EndTime;
        $slot->Status = 'Available'; // Mặc định là 'Available'
        $slot->save();

        // 4. Trả về thông báo thành công
        return response()->json([
            'message' => 'Tạo lịch trống thành công!',
            'slot' => $slot
        ], 201); // 201 Created
    }
    public function staffStore(Request $request)
    {
        $request->validate([
            'DoctorID' => 'required|integer|exists:doctors,DoctorID', // <-- Staff phải chọn Bác sĩ
            'StartTime' => 'required|date|after:now',
            'EndTime' => 'required|date|after:StartTime',
        ]);

        $slot = new DoctorAvailability();
        $slot->DoctorID = $request->DoctorID;
        $slot->StartTime = $request->StartTime;
        $slot->EndTime = $request->EndTime;
        $slot->Status = 'Available';
        $slot->save();

        return response()->json(['message' => 'Staff tạo lịch trống thành công!', 'slot' => $slot], 201);
    }

    /**
     * HÀM MỚI (Staff): Xoá slot rảnh.
     */
    public function staffDestroy($id)
    {
        $slot = DoctorAvailability::findOrFail($id);

        if ($slot->Status === 'Booked') {
            return response()->json(['message' => 'Không thể xoá slot đã có người đặt.'], 422);
        }

        $slot->delete();
        return response()->json(['message' => 'Xoá slot thành công.'], 200);
    }
    /**
     * HÀM MỚI (Doctor): Bác sĩ Xóa (khóa) slot rảnh CỦA MÌNH.
     * Chạy khi gọi DELETE /api/doctor/availability/{id}
     */
    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctorProfile;
        $slot = DoctorAvailability::findOrFail($id);

        // 1. === LOGIC PHÂN QUYỀN (AUTHORIZATION) ===
        // Bác sĩ có phải là "chủ" của slot này không?
        if ($doctor->DoctorID !== $slot->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền xóa slot này.'], 403);
        }

        // 2. === LOGIC NGHIỆP VỤ (BUSINESS LOGIC) ===
        // Chỉ cho phép xóa slot còn "Available"
        if ($slot->Status === 'Booked') {
            return response()->json(['message' => 'Không thể xóa slot đã có người đặt. Bạn phải hủy lịch hẹn trước.'], 422);
        }

        // 3. Xóa
        $slot->delete();

        // 204 No Content: Thành công, không cần trả về nội dung
        return response()->json(null, 204); 
    }
    /**
     * HÀM MỚI (Staff): Cập nhật một slot rảnh (thay mặt Bác sĩ).
     * Chạy khi gọi PUT /api/staff/availability/{id}
     */
    public function staffUpdate(Request $request, $id)
    {
        // 1. Tìm slot
        $slot = DoctorAvailability::findOrFail($id);

        // 2. Logic nghiệp vụ: Cấm sửa slot đã có người đặt
        if ($slot->Status === 'Booked') {
            return response()->json(['message' => 'Không thể sửa slot đã có người đặt.'], 422);
        }

        // 3. Validate (Kiểm tra) dữ liệu mới
        $request->validate([
            'DoctorID' => 'required|integer|exists:doctors,DoctorID',
            'StartTime' => 'required|date|after_or_equal:now',
            'EndTime' => 'required|date|after:StartTime',
        ]);

        // 4. Cập nhật và lưu
        $slot->DoctorID = $request->DoctorID;
        $slot->StartTime = $request->StartTime;
        $slot->EndTime = $request->EndTime;
        // (Status vẫn giữ là 'Available')
        $slot->save();

        // 5. Trả về thông báo thành công
        return response()->json([
            'message' => 'Cập nhật slot thành công!',
            'slot' => $slot
        ], 200); // 200 OK
    }
    /**
     * Lấy danh sách các slot còn trống ('Available') cho Bệnh nhân.
     * Chạy khi gọi GET /api/available-slots?date=...
     */
    public function getAvailableSlots(Request $request)
    {
        // 1. Validate tham số ngày
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $targetDate = $request->query('date');

        $slots = \App\Models\DoctorAvailability::where('Status', 'Available')
            // Chỉ lấy các slot bắt đầu vào ngày được chỉ định
            ->whereDate('StartTime', $targetDate)
            ->orderBy('StartTime', 'asc')
            // Lấy kèm thông tin bác sĩ và chuyên khoa để hiển thị ở frontend
            ->with('doctor.specialty') 
            ->get();

        return response()->json($slots, 200, [], JSON_UNESCAPED_UNICODE);
    }
}