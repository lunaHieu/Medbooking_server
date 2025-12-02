<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import tất cả Controller
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DoctorAvailabilityController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\ServiceController;

// Doctor Controllers
use App\Http\Controllers\Api\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Api\Doctor\ScheduleController;
use App\Http\Controllers\Api\Doctor\QueueController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\DoctorManagementController;
use App\Http\Controllers\Api\Admin\AppointmentManagementController;
use App\Http\Controllers\Api\Admin\SpecialtyController as AdminSpecialtyController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\Admin\PatientController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\FeedbackController;
use App\Http\Controllers\Api\Admin\NotificationController;

// Staff Controllers
use App\Http\Controllers\Api\Staff\DashboardController as StaffDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// === PUBLIC ROUTES (Không cần đăng nhập) ===
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/specialties', [SpecialtyController::class, 'index']);
Route::get('/specialties/{id}', [SpecialtyController::class, 'show']);
Route::get('/specialties/{id}/availability', [SpecialtyController::class, 'getAvailability']);

Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/{id}', [DoctorController::class, 'show']);
Route::get('/doctors/{id}/availability', [DoctorController::class, 'getAvailability']);

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);

// === PROTECTED ROUTES (Cần đăng nhập) ===
Route::middleware('auth:sanctum')->group(function () {

    // Thông tin user hiện tại
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // === BỆNH NHÂN ===
    Route::get('/my-appointments', [AppointmentController::class, 'myAppointments']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{id}/feedback', [AppointmentController::class, 'submitFeedback']);
    Route::post('/system-feedback', [AppointmentController::class, 'submitSystemFeedback']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/upload-avatar', [AuthController::class, 'uploadAvatar']);

    // === BÁC SĨ (Chỉ bác sĩ mới vào được) ===
    Route::middleware('role:BacSi')->prefix('doctor')->group(function () {
        Route::get('/dashboard-stats', [DoctorDashboardController::class, 'index']);
        Route::get('/my-schedule', [ScheduleController::class, 'index']);
        Route::get('/queue', [QueueController::class, 'index']);
        Route::get('/my-medical-records', [MedicalRecordController::class, 'myMedicalRecords']);

        Route::post('/availability', [DoctorAvailabilityController::class, 'store']);
        Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'destroy']);
        Route::get('/appointments/{id}', [AppointmentController::class, 'doctorShowAppointment']);
        Route::post('/medical-records', [MedicalRecordController::class, 'store']);
        Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update']);
        Route::post('/medical-records/{id}/upload-result', [MedicalRecordController::class, 'uploadResult']);
        Route::get('/patient-history/{patientId}', [MedicalRecordController::class, 'getPatientHistory']);
    });

    // === ADMIN + STAFF ===
    Route::middleware('role:QuanTriVien,NhanVien')->prefix('admin')->group(function () {
        // Quản lý bác sĩ
        Route::post('/doctors', [DoctorManagementController::class, 'store']);
        Route::put('/doctors/{id}', [DoctorManagementController::class, 'update']);
        Route::delete('/doctors/{id}', [DoctorManagementController::class, 'destroy']);
        Route::post('/doctors/{id}/upload-image', [DoctorManagementController::class, 'uploadImage']);

        // Quản lý dịch vụ
        Route::post('/services', [AdminServiceController::class, 'store']);
        Route::put('/services/{id}', [AdminServiceController::class, 'update']);
        Route::delete('/services/{id}', [AdminServiceController::class, 'destroy']);

        // Quản lý chuyên khoa
        Route::post('/specialties', [AdminSpecialtyController::class, 'store']);
        Route::put('/specialties/{id}', [AdminSpecialtyController::class, 'update']);
        Route::delete('/specialties/{id}', [AdminSpecialtyController::class, 'destroy']);

        // Các route khác...
        Route::get('/all-appointments', [AppointmentManagementController::class, 'index']);
        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{id}', [PatientController::class, 'show']);
        Route::get('/medical-records', [MedicalRecordController::class, 'index']);
        Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show']);
    });

    // === STAFF RIÊNG ===
    Route::middleware('role:NhanVien')->prefix('staff')->group(function () {
        Route::get('/dashboard-stats', [StaffDashboardController::class, 'index']);
        Route::get('/pending-appointments', [AppointmentController::class, 'getPendingAppointments']);
        Route::post('/appointments', [AppointmentController::class, 'staffCreateAppointment']);
        Route::patch('/appointments/{id}/confirm', [AppointmentController::class, 'confirmAppointment']);
        Route::put('/appointments/{id}', [AppointmentController::class, 'staffUpdateAppointment']);
        Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'staffCancelAppointment']);
        Route::post('/availability', [DoctorAvailabilityController::class, 'staffStore']);
        Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'staffDestroy']);
        Route::patch('/appointments/{id}/check-in', [AppointmentController::class, 'checkInAppointment']);
        Route::put('/availability/{id}', [DoctorAvailabilityController::class, 'staffUpdate']);
    });
});