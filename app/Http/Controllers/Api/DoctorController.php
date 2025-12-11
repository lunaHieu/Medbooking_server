<?php
// Tên file: app/Http/Controllers/Api/DoctorController.php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Carbon\Carbon;

class DoctorController extends Controller
{
    /**
     * [API 1]: Lấy danh sách Bác sĩ (Tên, Bằng cấp, Mô tả, Chuyên khoa)
     * Endpoint: GET /api/doctors
     */
    public function index(Request $request)
    {
        $query = Doctor::with([
            'user:UserID,FullName',
            'specialty:SpecialtyID,SpecialtyName',
        ]);

        if ($request->filled('name')) {
            $search = $request->name;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('FullName', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('specialty_id')) {
            $query->where('SpecialtyID', $request->specialty_id);
        }

        $doctors = $query->get();

        $formattedDoctors = $doctors->map(function ($doctor) {
            return [
                'DoctorID'    => $doctor->DoctorID,
                'FullName'    => $doctor->user->FullName ?? 'N/A',
                'Degree'      => $doctor->Degree,
                'Description' => $doctor->Description,
                'Specialty'   => $doctor->specialty->SpecialtyName ?? 'Chưa xác định',
            ];
        });

        return response()->json($formattedDoctors, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * [API 2]: Lấy lịch khám khả dụng của Bác sĩ theo ID
     * Endpoint: GET /api/doctors/{id}/availability
     */
    public function getAvailability(Request $request, $doctorId)
    {
        $slots = DoctorAvailability::where('DoctorID', $doctorId)
            ->where('Status', 'Available')
            ->where('StartTime', '>=', Carbon::now()->startOfDay())
            ->orderBy('StartTime', 'asc')
            ->get();

        $groupedSlots = $slots->groupBy(function ($item) {
            return Carbon::parse($item->StartTime)->format('Y-m-d');
        })->map(function ($daySlots, $date) {
            $dateObject = Carbon::parse($date);

            return [
                'date'      => $date,
                'dayOfWeek' => $dateObject->locale('vi')->dayName,
                'slots'     => $daySlots->map(function ($slot) {
                    return [
                        'SlotID'    => $slot->SlotID,
                        'StartTime' => Carbon::parse($slot->StartTime)->format('H:i'),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($groupedSlots, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /* =========================================================
     *                  CRUD CHO BÁC SĨ
     * ========================================================= */

    /**
     * [API 3]: Tạo bác sĩ
     * POST /api/doctors
     */
    public function store(Request $request)
    {
        $request->validate([
            'UserID'      => 'required|integer',
            'SpecialtyID' => 'required|integer',
            'Degree'      => 'required|string',
            'Description' => 'nullable|string',
        ]);

        $doctor = Doctor::create([
            'UserID'      => $request->UserID,
            'SpecialtyID' => $request->SpecialtyID,
            'Degree'      => $request->Degree,
            'Description' => $request->Description,
        ]);

        return response()->json([
            'message' => 'Tạo bác sĩ thành công',
            'doctor'  => $doctor
        ], 201);
    }

    /**
     * [API 4]: Cập nhật bác sĩ
     * PUT /api/doctors/{id}
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Không tìm thấy bác sĩ'], 404);
        }

        $doctor->update([
            'UserID'      => $request->UserID ?? $doctor->UserID,
            'SpecialtyID' => $request->SpecialtyID ?? $doctor->SpecialtyID,
            'Degree'      => $request->Degree ?? $doctor->Degree,
            'Description' => $request->Description ?? $doctor->Description,
        ]);

        return response()->json([
            'message' => 'Cập nhật bác sĩ thành công',
            'doctor'  => $doctor
        ]);
    }

    /**
     * [API 5]: Xóa bác sĩ
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
