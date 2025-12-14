<?php
// Tên file: app/Http/Controllers/Api/DoctorController.php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\Doctor;
<<<<<<< HEAD
use App\Models\DoctorAvailability;
use App\Models\MedicalRecord;
use App\Models\User;
use Carbon\Carbon;
=======
use App\Models\Specialty;
use App\Models\DoctorAvailability;
>>>>>>> tung-feature-doctor-dashboard

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
<<<<<<< HEAD
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
    public function getProfile(Request $request)
    {
        $userId = $request->user()->UserID;

        $userWithDoctorInfo = User::with(['doctor.specialty'])
            ->where('UserID', $userId)
            ->first();

        if (!$userWithDoctorInfo) {
            return response()->json(['message' => 'Không tìm thấy người dùng'], 404);
        }

        if (!$userWithDoctorInfo->doctor) {
            return response()->json(['message' => 'Tài khoản này chưa cập nhật hồ sơ bác sĩ'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $userWithDoctorInfo
        ]);
    }
}
=======
   public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',         
                'phone' => 'nullable|string|max:20',                
                'specialty_id' => 'nullable|integer|exists:specialties,SpecialtyID', 
                'degree' => 'nullable|string|max:100',              
                'years_of_experience' => 'nullable|integer|min:0',  
                'profile_description' => 'nullable|string',         
            ]);
            
            // Cập nhật User
            $user->update([
                'FullName' => $validated['full_name'],            
                'PhoneNumber' => $validated['phone'] ?? $user->PhoneNumber,
            ]);
            
            // Cập nhật Doctor
            if ($user->doctorProfile) {
                $doctorData = [
                    'SpecialtyID' => $validated['specialty_id'] ?? null,  
                    'Degree' => $validated['degree'] ?? null,
                    'YearsOfExperience' => $validated['years_of_experience'] ?? null,
                    'ProfileDescription' => $validated['profile_description'] ?? null,
                ];
                $user->doctorProfile->update($doctorData); // <-- Dòng này phải liền với trên, không có git log
            } else {
                Doctor::create([
                    'DoctorID' => $user->UserID,
                    'SpecialtyID' => $validated['specialty_id'] ?? null,
                    'Degree' => $validated['degree'] ?? null,
                    'YearsOfExperience' => $validated['years_of_experience'] ?? null,
                    'ProfileDescription' => $validated['profile_description'] ?? null,
                ]);
            }
            
            // Refresh và response
            $user->refresh();
            $user->load(['doctorProfile.specialty']);
            
            $specialtyData = null;
            if ($user->doctorProfile && $user->doctorProfile->specialty) {
                $specialtyData = [
                    'SpecialtyID' => $user->doctorProfile->specialty->SpecialtyID,
                    'SpecialtyName' => $user->doctorProfile->specialty->SpecialtyName
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật hồ sơ thành công',
                'data' => [
                    'FullName' => $user->FullName,
                    'Email' => $user->Email,
                    'PhoneNumber' => $user->PhoneNumber,
                    'doctor' => $user->doctorProfile ? [
                        'DoctorID' => $user->doctorProfile->DoctorID,
                        'SpecialtyID' => $user->doctorProfile->SpecialtyID,
                        'Degree' => $user->doctorProfile->Degree,
                        'YearsOfExperience' => $user->doctorProfile->YearsOfExperience,
                        'ProfileDescription' => $user->doctorProfile->ProfileDescription,
                        'imageURL' => $user->doctorProfile->imageURL,
                        'specialty' => $specialtyData
                    ] : null
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Update doctor profile error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

public function index()
{
    try {
        $doctors = User::where('Role', 'BacSi')
            ->with(['doctorProfile.specialty'])
            ->get();

        $doctorsData = $doctors->map(function ($user) {
            if (!$user->doctorProfile) {
                return null; // Bỏ qua nếu chưa có hồ sơ bác sĩ
            }

            $specialty = $user->doctorProfile->specialty;

            return [
                'UserID' => $user->UserID,
                'FullName' => $user->FullName,
                'PhoneNumber' => $user->PhoneNumber,
                'Email' => $user->Email,
                'avatar_url' => $user->avatar_url ? asset('storage/' . $user->avatar_url) : null,
                'doctor' => [
                    'DoctorID' => $user->doctorProfile->DoctorID,
                    'SpecialtyID' => $user->doctorProfile->SpecialtyID,
                    'Degree' => $user->doctorProfile->Degree,
                    'YearsOfExperience' => $user->doctorProfile->YearsOfExperience,
                    'ProfileDescription' => $user->doctorProfile->ProfileDescription,
                    'imageURL' => $user->doctorProfile->imageURL ? asset('storage/' . $user->doctorProfile->imageURL) : null,
                    'specialty' => $specialty ? [
                        'SpecialtyID' => $specialty->SpecialtyID,
                        'SpecialtyName' => $specialty->SpecialtyName,
                        'Description' => $specialty->Description,
                        'imageURL' => $specialty->imageURL,
                    ] : null,
                ]
            ];
        })->filter()->values(); // Loại bỏ null và reset key

        return response()->json([
            'success' => true,
            'data' => $doctorsData,
        ]);

    } catch (\Exception $e) {
        \Log::error('Doctor index error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'Lỗi tải danh sách bác sĩ',
            'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}


public function getAvailability($doctorId)
{
    // 1️⃣ Check doctor tồn tại
    $doctor = Doctor::find($doctorId);

    if (!$doctor) {
        return response()->json([
            'message' => 'Doctor not found'
        ], 404);
    }

    // 2️⃣ Lấy slot khả dụng
    $slots = DoctorAvailability::where('DoctorID', $doctorId)
        ->where('Status', 'available')
        ->get();

    // 3️⃣ Trả về array (kể cả rỗng)
    return response()->json($slots, 200);
}

/**
 * GET /api/doctors/{id} - Chi tiết bác sĩ public
 */
public function show($id)
{
    try {
        $user = User::where('Role', 'BacSi')
            ->where('UserID', $id)
            ->with(['doctorProfile.specialty'])
            ->first();

        if (!$user || !$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bác sĩ'
            ], 404);
        }

        $specialty = $user->doctorProfile->specialty;

        $data = [
            'UserID' => $user->UserID,
            'FullName' => $user->FullName,
            'PhoneNumber' => $user->PhoneNumber,
            'Email' => $user->Email,
            'avatar_url' => $user->avatar_url ? asset('storage/' . $user->avatar_url) : null,
            'doctor' => [
                'DoctorID' => $user->doctorProfile->DoctorID,
                'SpecialtyID' => $user->doctorProfile->SpecialtyID,
                'Degree' => $user->doctorProfile->Degree,
                'YearsOfExperience' => $user->doctorProfile->YearsOfExperience,
                'ProfileDescription' => $user->doctorProfile->ProfileDescription,
                'imageURL' => $user->doctorProfile->imageURL ? asset('storage/' . $user->doctorProfile->imageURL) : null,
                'specialty' => $specialty ? [
                    'SpecialtyID' => $specialty->SpecialtyID,
                    'SpecialtyName' => $specialty->SpecialtyName,
                    'Description' => $specialty->Description,
                    'imageURL' => $specialty->imageURL,
                ] : null,
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);

    } catch (\Exception $e) {
        \Log::error('Doctor show error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Lỗi tải thông tin bác sĩ'
        ], 500);
    }
}

}
>>>>>>> tung-feature-doctor-dashboard
