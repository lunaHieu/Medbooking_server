<?php
// Tên file: app/Http/Controllers/Api/DoctorController.php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\MedicalRecord;
use App\Models\User;
use Carbon\Carbon;

class DoctorController extends Controller
{
    /**
     * Lấy danh sách Bác sĩ (Tên, Bằng cấp, Mô tả, Chuyên khoa)
     * GET /api/doctors
     */
    public function index(Request $request)
    {
        // Query chung
        $query = Doctor::with(['user', 'specialty']);

        // Tìm theo tên
        if ($request->filled('name')) {
            $search = $request->name;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('FullName', 'like', '%' . $search . '%');
            });
        }

        // Lọc theo chuyên khoa
        if ($request->filled('specialty_id')) {
            $query->where('SpecialtyID', $request->specialty_id);
        }

        // Tìm theo tên (search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('FullName', 'like', "%{$search}%");
            });
        }

        $doctors = $query->get();

        return response()->json($doctors, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lấy lịch khám khả dụng của 1 bác sĩ
     * GET /api/doctors/{id}/availability
     */
    public function getAvailability(Request $request, $id)
    {
        $date = $request->input('date');

        $query = Doctor::findOrFail($id)
            ->availabilitySlots()
            ->where('Status', 'Available')
            ->where('StartTime', '>', Carbon::now());

        // Nếu có chọn ngày thì lọc theo ngày
        if ($date) {
            $query->whereDate('StartTime', $date);
        }

        $availableSlots = $query->orderBy('StartTime', 'asc')->get();

        return response()->json($availableSlots, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Tạo bác sĩ
     * POST /api/doctors
     */
    public function store(Request $request)
    {
        $request->validate([
            'UserID' => 'required|integer',
            'SpecialtyID' => 'required|integer',
            'Degree' => 'required|string',
            'Description' => 'nullable|string',
        ]);

        $doctor = Doctor::create([
            'UserID' => $request->UserID,
            'SpecialtyID' => $request->SpecialtyID,
            'Degree' => $request->Degree,
            'Description' => $request->Description,
        ]);

        return response()->json([
            'message' => 'Tạo bác sĩ thành công',
            'doctor' => $doctor
        ], 201);
    }

    /**
     * Lấy chi tiết bác sĩ
     * GET /api/doctors/{id}
     */
    public function show($id)
    {
        $doctor = Doctor::with(['user', 'specialty'])->findOrFail($id);

        return response()->json($doctor);
    }

    /**
     * Cập nhật bác sĩ
     * PUT /api/doctors/{id}
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json(['message' => 'Không tìm thấy bác sĩ'], 404);
        }

        $doctor->update([
            'UserID' => $request->UserID ?? $doctor->UserID,
            'SpecialtyID' => $request->SpecialtyID ?? $doctor->SpecialtyID,
            'Degree' => $request->Degree ?? $doctor->Degree,
            'Description' => $request->Description ?? $doctor->Description,
        ]);

        return response()->json([
            'message' => 'Cập nhật bác sĩ thành công',
            'doctor' => $doctor
        ]);
    }

    /**
     * Xóa bác sĩ
     * DELETE /api/doctors/{id}
     */
    public function destroy($id)
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json(['message' => 'Không tìm thấy bác sĩ'], 404);
        }

        $doctor->delete();

        return response()->json(['message' => 'Xóa bác sĩ thành công']);
    }
}
