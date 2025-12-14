<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DoctorAvailabilityController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\Admin\FeedbackController;


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

// Staff Controllers
use App\Http\Controllers\Api\Staff\DashboardController as StaffDashboardController;

// =======================================================
// PUBLIC TEST ROUTES 
// =======================================================
Route::get('/test-public', function () {
    return response()->json([
        'success' => true,
        'message' => 'Public API is working!',
        'timestamp' => now()->format('Y-m-d H:i:s'),
        'endpoints' => [
            '/api/test-public',
            '/api/doctor/test-public',
            '/api/login',
            '/api/register',
        ],
    ]);
});

Route::get('/doctor/test-public', function () {
    return response()->json([
        'success' => true,
        'message' => 'Doctor public test route works!',
        'timestamp' => now()->format('Y-m-d H:i:s'),
        'data' => [
            'doctor_routes' => [
                '/api/doctor/dashboard',
                '/api/doctor/dashboard-stats',
                '/api/doctor/my-schedule',
                '/api/doctor/schedule',
                '/api/doctor/queue',
                '/api/doctor/profile',
            ],
        ],
    ]);
});

// =======================================================
// PUBLIC ROUTES 
// =======================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/specialties', [SpecialtyController::class, 'index']);
Route::get('/specialties/{id}', [SpecialtyController::class, 'show']);
Route::get('/specialties/{id}/availability', [SpecialtyController::class, 'getAvailability']);

Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/{id}', [DoctorController::class, 'show']);
Route::get('/doctors/{id}/availability', [DoctorController::class, 'getAvailability']);

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);

// =======================================================
// HEALTH CHECK
// =======================================================
Route::get('/health', function () {
    $db = 'unknown';
    try {
        DB::connection()->getPdo();
        $db = 'connected';
    } catch (\Throwable $e) {
        $db = 'disconnected';
    }

    return response()->json([
        'status' => 'healthy',
        'service' => 'Medbooking API',
        'version' => '1.0.0',
        'timestamp' => now()->format('Y-m-d H:i:s'),
        'database' => $db,
    ]);
});

// =======================================================
// PROTECTED ROUTES 
// =======================================================
Route::middleware('auth:sanctum')->group(function () {

    // -----------------------------
    // CURRENT USER 
    // -----------------------------
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    });

    // -----------------------------
    // LOGOUT
    // -----------------------------
    Route::post('/logout', [AuthController::class, 'logout']);

    // -----------------------------
    // TEST PROTECTED
    // -----------------------------
    Route::get('/test-protected', function (Request $request) {
        $u = $request->user();
        return response()->json([
            'success' => true,
            'message' => 'Protected API route works!',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $u->UserID ?? $u->id ?? null,
                'name' => $u->FullName ?? $u->name ?? null,
                'role' => $u->Role ?? $u->role ?? null,
                'email' => $u->Email ?? $u->email ?? null,
            ],
        ]);
    });

    // ===================================================
    // BỆNH NHÂN
    // ===================================================
    Route::get('/my-appointments', [AppointmentController::class, 'myAppointments']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{id}/feedback', [AppointmentController::class, 'submitFeedback']);
    Route::post('/system-feedback', [AppointmentController::class, 'submitSystemFeedback']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/upload-avatar', [AuthController::class, 'uploadAvatar']);

    // ===================================================
    // BÁC SĨ 
    // ===================================================
    Route::middleware('role:BacSi')->prefix('doctor')->group(function () {

        // DASHBOARD
        Route::get('/dashboard', [DoctorDashboardController::class, 'index']);
        Route::get('/dashboard-stats', [DoctorDashboardController::class, 'index']);

        // SCHEDULE
        Route::get('/my-schedule', [ScheduleController::class, 'index']);
        Route::get('/schedule', [AppointmentController::class, 'getSchedule']);

        // QUEUE
        Route::get('/queue', [QueueController::class, 'index']);

        // ✅ PROFILE - ENDPOINT QUAN TRỌNG
       Route::get('/profile', [DoctorController::class, 'getProfile']);
Route::put('/profile', [DoctorController::class, 'updateProfile']);



        Route::patch('/appointments/{id}/status',
    [AppointmentController::class, 'updateStatus']
);


        // MEDICAL RECORDS
        Route::get('/my-medical-records', [MedicalRecordController::class, 'myMedicalRecords']);

        // AVAILABILITY
        Route::post('/availability', [DoctorAvailabilityController::class, 'store']);
        Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'destroy']);

        // APPOINTMENTS & MEDICAL
        Route::get('/appointments/{id}', [AppointmentController::class, 'doctorShowAppointment']);
        Route::post('/medical-records', [MedicalRecordController::class, 'store']);
        Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update']);
        Route::post('/medical-records/{id}/upload-result', [MedicalRecordController::class, 'uploadResult']);
        Route::get('/patient-history/{patientId}', [MedicalRecordController::class, 'getPatientHistory']);

    });

    // ===================================================
    // ADMIN + STAFF
    // ===================================================
    Route::middleware('role:QuanTriVien,NhanVien')->prefix('admin')->group(function () {
        Route::post('/doctors', [DoctorManagementController::class, 'store']);
        Route::put('/doctors/{id}', [DoctorManagementController::class, 'update']);
        Route::delete('/doctors/{id}', [DoctorManagementController::class, 'destroy']);
        Route::post('/doctors/{id}/upload-image', [DoctorManagementController::class, 'uploadImage']);

        Route::post('/services', [AdminServiceController::class, 'store']);
        Route::put('/services/{id}', [AdminServiceController::class, 'update']);
        Route::delete('/services/{id}', [AdminServiceController::class, 'destroy']);

        Route::post('/specialties', [AdminSpecialtyController::class, 'store']);
        Route::put('/specialties/{id}', [AdminSpecialtyController::class, 'update']);
        Route::delete('/specialties/{id}', [AdminSpecialtyController::class, 'destroy']);

        Route::get('/all-appointments', [AppointmentManagementController::class, 'index']);
        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{id}', [PatientController::class, 'show']);
        Route::get('/medical-records', [MedicalRecordController::class, 'index']);
        Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show']);

        Route::get('/users', [PatientController::class, 'index']); 
    Route::get('/users/{id}', [PatientController::class, 'show']);
    Route::get('/services', [AdminServiceController::class, 'index']);
      Route::get('/feedbacks', [FeedbackController::class, 'index']);
    });

    // ===================================================
    // STAFF ONLY
    // ===================================================
    Route::middleware('role:NhanVien')->prefix('staff')->group(function () {
        Route::get('/dashboard-stats', [StaffDashboardController::class, 'index']);
        Route::get('/pending-appointments', [AppointmentController::class, 'getPendingAppointments']);
        Route::post('/appointments', [AppointmentController::class, 'staffCreateAppointment']);
        Route::patch('/appointments/{id}/confirm', [AppointmentController::class, 'confirmAppointment']);
        Route::put('/appointments/{id}', [AppointmentController::class, 'staffUpdateAppointment']);
        Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'staffCancelAppointment']);
        Route::patch('/appointments/{id}/check-in', [AppointmentController::class, 'checkInAppointment']);
        Route::post('/availability', [DoctorAvailabilityController::class, 'staffStore']);
        Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'staffDestroy']);
        Route::put('/availability/{id}', [DoctorAvailabilityController::class, 'staffUpdate']);
    });
});

// =======================================================
// TEST ROUTES
// =======================================================
Route::prefix('test')->group(function () {
    Route::get('/doctor-dashboard-stats', [DoctorDashboardController::class, 'testData']);

    Route::get('/queue-test', [QueueController::class, 'testData']);
    
    Route::get('/my-medical-records-test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Medical records test data',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'data' => [
                [
                    'id' => 1,
                    'patientName' => "Trần Thị Lan",
                    'age' => 34,
                    'diagnosis' => "Viêm họng cấp do virus",
                    'treatment' => "Kháng sinh 5 ngày, nghỉ ngơi, uống nhiều nước, hạ sốt khi cần",
                    'prescriptions' => [
                        ['medicine' => "Amoxicillin", 'dosage' => "500mg", 'frequency' => "3 lần/ngày"],
                        ['medicine' => "Paracetamol", 'dosage' => "500mg", 'frequency' => "Khi sốt >38.5°C"],
                    ],
                    'tests' => ["Xét nghiệm máu", "Ngoáy họng", "CRP"],
                    'date' => "2025-04-02",
                    'status' => "completed",
                ],
            ],
        ]);
    });
});

// =======================================================
// SIMPLE UPDATE
// =======================================================
Route::patch('/simple-update/{id}', function ($id) {
    error_log("=== SIMPLE UPDATE CALLED ===");
    error_log("Appointment ID: " . $id);
    error_log("Request data: " . file_get_contents('php://input'));

    $appointment = \App\Models\Appointment::find($id);

    if (!$appointment) {
        return response()->json([
            'success' => false,
            'message' => 'Appointment not found',
        ], 404);
    }

    $appointment->Status = 'in_progress';
    $appointment->save();

    error_log("Updated appointment " . $id . " to in_progress");

    return response()->json([
        'success' => true,
        'message' => 'Updated successfully (SIMPLE ROUTE)',
        'data' => $appointment,
    ]);
});