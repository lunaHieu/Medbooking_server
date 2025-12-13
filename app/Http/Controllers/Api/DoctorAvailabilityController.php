<?php
// Tên file: app/Http/Controllers/Api/DoctorAvailabilityController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DoctorAvailability;
use Illuminate\Support\Facades\Auth;

class DoctorAvailabilityController extends Controller
{
    /**
     * Bác sĩ tự tạo slot trống
     * POST /api/doctor/availability
     */
    public function store(Request $request)
    {
        $request->validate([
            'StartTime' => 'required|date|after:now',
            'EndTime' => 'required|date|after:StartTime',
        ]);

        $doctor = Auth::user()->doctorProfile;

        $slot = new DoctorAvailability();
        $slot->DoctorID = $doctor->DoctorID;
        $slot->StartTime = $request->StartTime;
        $slot->EndTime = $request->EndTime;
        $slot->Status = 'Available';
        $slot->save();

        return response()->json([
            'message' => 'Tạo lịch trống thành công!',
            'slot' => $slot
        ], 201);
    }

    /**
     * Staff tạo slot thay cho bác sĩ
     * POST /api/staff/availability
     */
    public function staffStore(Request $request)
    {
        $request->validate([
            'DoctorID' => 'required|integer|exists:doctors,DoctorID',
            'StartTime' => 'required|date|after:now',
            'EndTime' => 'required|date|after:StartTime',
        ]);

        $slot = new DoctorAvailability();
        $slot->DoctorID = $request->DoctorID;
        $slot->StartTime = $request->StartTime;
        $slot->EndTime = $request->EndTime;
        $slot->Status = 'Available';
        $slot->save();

        return response()->json([
            'message' => 'Staff tạo lịch trống thành công!',
            'slot' => $slot
        ], 201);
    }

    /**
     * Staff xóa slot
     * DELETE /api/staff/availability/{id}
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
     * Bác sĩ xóa slot của chính mình
     * DELETE /api/doctor/availability/{id}
     */
    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctorProfile;
        $slot = DoctorAvailability::findOrFail($id);

        if ($doctor->DoctorID !== $slot->DoctorID) {
            return response()->json(['message' => 'Bạn không có quyền xóa slot này.'], 403);
        }

        if ($slot->Status === 'Booked') {
            return response()->json(['message' => 'Không thể xóa slot đã có người đặt.'], 422);
        }

        $slot->delete();
        return response()->json(null, 204);
    }

    /**
     * Staff cập nhật slot của bác sĩ
     * PUT /api/staff/availability/{id}
     */
    public function staffUpdate(Request $request, $id)
    {
        $slot = DoctorAvailability::findOrFail($id);

        if ($slot->Status === 'Booked') {
            return response()->json(['message' => 'Không thể sửa slot đã có người đặt.'], 422);
        }

        $request->validate([
            'DoctorID' => 'required|integer|exists:doctors,DoctorID',
            'StartTime' => 'required|date|after_or_equal:now',
            'EndTime' => 'required|date|after:StartTime',
        ]);

        $slot->DoctorID = $request->DoctorID;
        $slot->StartTime = $request->StartTime;
        $slot->EndTime = $request->EndTime;
        $slot->save();

        return response()->json([
            'message' => 'Cập nhật slot thành công!',
            'slot' => $slot
        ], 200);
    }

    /**
     * (Bệnh nhân) Lấy danh sách slot trống của tất cả bác sĩ theo ngày
     * GET /api/available-slots?date=YYYY-mm-dd
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $targetDate = $request->query('date');

        $slots = DoctorAvailability::where('Status', 'Available')
            ->whereDate('StartTime', $targetDate)
            ->orderBy('StartTime', 'asc')
            ->with('doctor.specialty')
            ->get();

        return response()->json($slots, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * (Bác sĩ) Xem danh sách slot rảnh của chính mình
     * GET /api/doctor/availability?date=...
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->doctorProfile) {
            return response()->json(['message' => 'Tài khoản này chưa có thông tin Bác sĩ'], 403);
        }

        $doctorId = $user->doctorProfile->DoctorID;

        $date = $request->query('date');

        $query = DoctorAvailability::where('DoctorID', $doctorId)
            ->orderBy('StartTime', 'asc');

        if ($date) {
            $query->whereDate('StartTime', $date);
        } else {
            $query->where('StartTime', '>=', now());
        }

        $slots = $query->get();

        return response()->json($slots);
    }
}
