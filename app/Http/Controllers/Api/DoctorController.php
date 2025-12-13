<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;

class DoctorController extends Controller
{
    /**
     * GET /api/doctor/profile
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            // ✅ Tải quan hệ với chuyên khoa
            $user->load(['doctorProfile.specialty']);
            
            if (!$user->doctorProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản chưa được gán hồ sơ bác sĩ'
                ], 404);
            }
            
            // ✅ Chuẩn bị dữ liệu chuyên khoa
            $specialtyData = null;
            if ($user->doctorProfile->specialty) {
                $specialtyData = [
                    'SpecialtyID' => $user->doctorProfile->specialty->SpecialtyID,
                    'SpecialtyName' => $user->doctorProfile->specialty->SpecialtyName
                ];
            } else if ($user->doctorProfile->SpecialtyID) {
                // Nếu có SpecialtyID nhưng chưa load relationship
                $specialty = Specialty::find($user->doctorProfile->SpecialtyID);
                if ($specialty) {
                    $specialtyData = [
                        'SpecialtyID' => $specialty->SpecialtyID,
                        'SpecialtyName' => $specialty->SpecialtyName
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->UserID,
                    'FullName' => $user->FullName,
                    'Email' => $user->Email,
                    'PhoneNumber' => $user->PhoneNumber,
                    'Role' => $user->Role,
                    'doctor_profile' => [
                        'DoctorID' => $user->doctorProfile->DoctorID,
                        'SpecialtyID' => $user->doctorProfile->SpecialtyID,
                        'Degree' => $user->doctorProfile->Degree,
                        'YearsOfExperience' => $user->doctorProfile->YearsOfExperience,
                        'ProfileDescription' => $user->doctorProfile->ProfileDescription,
                        'imageURL' => $user->doctorProfile->imageURL,
                        'specialty' => $specialtyData // ✅ Đảm bảo không null
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Doctor profile error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PUT /api/doctor/profile
     */
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
        
        // ✅ Cập nhật User
        $user->update([
            'FullName' => $validated['full_name'],            
            'PhoneNumber' => $validated['phone'] ?? $user->PhoneNumber,
        ]);
        
        // ✅ Cập nhật Doctor
        if ($user->doctorProfile) {
            $doctorData = [
                'SpecialtyID' => $validated['specialty_id'] ?? null,  
                'Degree' => $validated['degree'] ?? null,
                'YearsOfExperience' => $validated['years_of_experience'] ?? null,
                'ProfileDescription' => $validated['profile_description'] ?? null,
            ];
            
            $user->doctorProfile->update($doctorData);
        } else {
            Doctor::create([
                'UserID' => $user->UserID,
                'SpecialtyID' => $validated['specialty_id'] ?? null,
                'Degree' => $validated['degree'] ?? null,
                'YearsOfExperience' => $validated['years_of_experience'] ?? null,
                'ProfileDescription' => $validated['profile_description'] ?? null,
            ]);
        }
        
        // ✅ Refresh và response
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
}