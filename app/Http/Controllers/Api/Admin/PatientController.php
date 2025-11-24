<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Lấy danh sách Bệnh nhân (Hỗ trợ tìm kiếm)
     * GET /api/admin/patients?search=...
     */
    public function index(Request $request)
    {
        // Chỉ lấy User có Role là 'BenhNhan'
        $query = User::where('Role', 'BenhNhan');

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('FullName', 'like', '%' . $searchTerm . '%')
                  ->orWhere('PhoneNumber', 'like', '%' . $searchTerm . '%')
                  ->orWhere('Username', 'like', '%' . $searchTerm . '%');
            });
        }

        $patients = $query->orderBy('created_at', 'desc')->get();
        return response()->json($patients, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Xem chi tiết 1 Bệnh nhân
     * GET /api/admin/patients/{id}
     */
    public function show($id)
    {
        $patient = User::where('Role', 'BenhNhan')->findOrFail($id);
        return response()->json($patient, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Xem lịch sử khám bệnh của Bệnh nhân
     * GET /api/admin/patients/{id}/history
     */
    public function getHistory($id)
    {
        $patient = User::where('Role', 'BenhNhan')->findOrFail($id);
        
        // Lấy danh sách lịch hẹn (appointments) của bệnh nhân này
        // Kèm theo thông tin Bác sĩ và Bệnh án (nếu có)
        $history = $patient->appointmentsAsPatient()
                            ->with(['doctor.user', 'medicalRecord'])
                            ->orderBy('StartTime', 'desc')
                            ->get();

        return response()->json($history, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Tạo Bệnh nhân mới
     * POST /api/admin/patients
     */
    public function store(Request $request)
    {
        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => 'required|string|max:100|unique:users',
            'PhoneNumber' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $patient = new User();
        $patient->fill($request->all());
        $patient->password = Hash::make($request->password);
        $patient->Role = 'BenhNhan';
        $patient->Status = 'HoatDong';
        $patient->save();

        return response()->json(['message' => 'Tạo bệnh nhân thành công!', 'patient' => $patient], 201);
    }

    /**
     * Cập nhật Bệnh nhân
     * PUT /api/admin/patients/{id}
     */
    public function update(Request $request, $id)
    {
        $patient = User::where('Role', 'BenhNhan')->findOrFail($id);

        $request->validate([
            'FullName' => 'required|string|max:255',
            'Username' => ['required', Rule::unique('users')->ignore($patient->UserID, 'UserID')],
            'PhoneNumber' => ['required', Rule::unique('users')->ignore($patient->UserID, 'UserID')],
        ]);

        $patient->update($request->except(['password', 'Role'])); // Không cho sửa Role ở đây

        return response()->json(['message' => 'Cập nhật thành công!', 'patient' => $patient], 200);
    }

    /**
     * Xóa Bệnh nhân (Đã có)
     */
    public function destroy($id)
    {
        $patient = User::where('Role', 'BenhNhan')->findOrFail($id);
        $patient->delete();
        return response()->json(['message' => 'Xóa bệnh nhân thành công.'], 200);
    }
}