<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DoctorAvailabilityController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\Doctor\DashboardController as DoctorDashboardController;

use App\Http\Controllers\Api\Admin\DoctorManagementController;
use App\Http\Controllers\Api\Admin\AppointmentManagementController;
use App\Http\Controllers\Api\Admin\SpecialtyController as AdminSpecialtyController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\Admin\PatientController;
use App\Http\Controllers\Api\Admin\UserManagementController;

use App\Http\Controllers\Api\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Api\Admin\FeedbackController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\NotificationController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//route benh nhan (patient)
// CÁC ROUTE ĐƯỢC BẢO VỆ 
Route::middleware('auth:sanctum')->group(function () {
    //route GET /api/users
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    //Tự gọi bác sĩ của mình
    Route::get('/my-doctors', [AppointmentController::class, 'getMyDoctors']);
    // Trong nhóm Patient
    Route::post('/system-feedback', [AppointmentController::class, 'submitSystemFeedback']);
    Route::post('/appointments/{id}/feedback', [AppointmentController::class, 'submitFeedback']);
    // Trong nhóm Patient
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    //2. the route moi vao day
    //api de lay lich hen cua chinh toi
    Route::get('/my-appointments', [AppointmentController::class, 'myAppointments']);
    //sau nay se them route bao ve khac vd: POST /api/appointment vao day

    // API để tạo một lịch hẹn mới
    Route::post('/appointments', [AppointmentController::class, 'store']);

    //API de dang xuat
    Route::post('logout', [AuthController::class, 'logout']);

    //API de benh nhan tu huy lich 
    //{id} la id lich hen
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);

    // API để Bệnh nhân tự tải ảnh đại diện
    Route::post('/user/upload-avatar', [AuthController::class, 'uploadAvatar']);

    //Quản lí gia đình
    Route::get('user/family-members', [AuthController::class, 'getFamilyMembers']);
    Route::post('/user/family-members', [AuthController::class, 'addFamilyMembers']);
    Route::delete('user/family-members/{id}', [AuthController::class, 'removeFamilyMember']);
    Route::get('/users/search-public', [AuthController::class, 'searchUserPublic']);
    //Xem thông báo
    Route::get('/my-notifications', [NotificationController::class, 'getMyNotifications']);
    //đã đọc
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll']);
});

//CÁC ROUTE KHÔNG BẢO VỆ (Ai cũng có thể gọi được)
Route::get('/specialties', [SpecialtyController::class, 'index']);

//Route cho Đăng ký (Register)
Route::post('/register', [AuthController::class, 'register']);

//Route cho Đăng nhập (Login)
Route::post('/login', [AuthController::class, 'login']);
//Lấy các bác sĩ
Route::get('/doctors', [DoctorController::class, 'index']);
//Lấy lịch trống của bác sĩ cụ thể
Route::get('/doctors/{id}/availability', [DoctorController::class, 'getAvailability']);

//Lấy chi tiết 1 bác sĩ
Route::get('/doctors/{id}', [DoctorController::class, 'show']);
// Lấy lịch trống theo Chuyên khoa
Route::get('/specialties/{id}/availability', [SpecialtyController::class, 'getAvailability']);
//Lấy hết dịch vụ
Route::get('/services', [ServiceController::class, 'index']);
//Lấy chi tiết từng dịch vụ
Route::get('/services/{id}', [ServiceController::class, 'show']);
//Lấy chi tiết từng chuyên khoa
Route::get('/specialties/{id}', [SpecialtyController::class, 'show']);
//Gọi ra 3 feedbacks tốt nhất dựa theo số sao
Route::get('/top-feedbacks', [FeedbackController::class, 'getTopFeedbacks']);
//Nhom 3 :cac route cua bac si(dotor) 
//2 lop bao ve 
//1. auth:sanctum
//2. role:dotor
Route::middleware(['auth:sanctum', 'role:BacSi'])
    ->prefix('doctor')
    ->group(function () {

        // API để Bác sĩ tự tạo lịch trống
        Route::post('/availability', [App\Http\Controllers\Api\DoctorAvailabilityController::class, 'store']);

        // API Bác sĩ xem lịch hẹn đã đặt của mình
        Route::get('/my-schedule', [AppointmentController::class, 'doctorSchedule']);

        // API Bác sĩ tạo hồ sơ bệnh án
        Route::post('/medical-records', [MedicalRecordController::class, 'store']);

        // API Lấy hàng đợi (queue) - những người đã "CheckedIn"
        Route::get('/queue', [AppointmentController::class, 'getDoctorQueue']);
        // API Lấy số liệu Thống kê cho Homepage
        Route::get('/dashboard-stats', [DoctorDashboardController::class, 'index']);
        // API Bác sĩ Xóa (khóa) một slot rảnh
        Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'destroy']);
        // API Bác sĩ xem chi tiết 1 lịch hẹn
        Route::get('/appointments/{id}', [AppointmentController::class, 'doctorShowAppointment']);
        // API Bác sĩ tải file kết quả (X-quang...) lên 1 Bệnh án
        Route::post('/medical-records/{id}/upload-result', [MedicalRecordController::class, 'uploadResult']);
        // API Bác sĩ xem lại các bệnh án đã tạo
        Route::get('/my-medical-records', [MedicalRecordController::class, 'myMedicalRecords']);
        // API Bác sĩ xem lịch sử của 1 bệnh nhân
        Route::get('/patient-history/{patientId}', [MedicalRecordController::class, 'getPatientHistory']);
        // API Bác sĩ Sửa bệnh án
        Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update']);
        //API cập nhật trạng thái (Bắt đầu khám -> Hoàn tất)
        Route::put('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
        //API xem danh sách Slot rảnh của chính bác sĩ đó
        Route::get('/my-slots', [DoctorAvailabilityController::class, 'index']);
    });

//nhom4 admin
Route::middleware(['auth:sanctum', 'role:QuanTriVien,NhanVien'])->prefix('admin')->group(function () {

    // API để Admin tạo Bác sĩ mới
    Route::post('/doctors', [DoctorManagementController::class, 'store']);

    //api update bac si
    Route::put('/doctors/{id}', [DoctorManagementController::class, 'update']);

    // API để Admin xem tất cả Lịch hẹn
    Route::get('/all-appointments', [AppointmentManagementController::class, 'index']);

    //api de admin tai anh len cho bac si
    Route::post('/doctors/{id}/upload-image', [DoctorManagementController::class, 'uploadImage']);
    // API để Admin Xóa Bác sĩ
    Route::delete('/doctors/{id}', [DoctorManagementController::class, 'destroy']);
    // API để Admin Xóa Bệnh án
    Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy']);

    //QUẢN LÝ THÔNG BÁO
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/notifications/send', [AdminNotificationController::class, 'send']);
    Route::delete('/notifications/{id}', [AdminNotificationController::class, 'destroy']);
    Route::delete('/notifications/delete-all', [AdminNotificationController::class, 'destroyAll']);
    Route::post('/notifications/trigger-reminders', [AdminNotificationController::class, 'triggerReminders']);
    // Route cho bệnh nhân xem kết quả
    Route::get('/appointments/{id}/medical-record', [AppointmentController::class, 'getMedicalRecord']);

    // Route cho bác sĩ đặt lịch tái khám
    Route::post('/doctor/appointments/follow-up', [AppointmentController::class, 'createFollowUp']);

    // API tạo User chung (cho Staff, Admin, hoặc Bệnh nhân nhanh)
    Route::post('/users', [UserManagementController::class, 'store']);
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::get('/users/{id}', [UserManagementController::class, 'show']);
    Route::put('/users/{id}', [UserManagementController::class, 'update']);
    //Dịch vụ
    Route::get('/services', [AdminServiceController::class, 'index']);
    Route::get('/services/{id}', [AdminServiceController::class, 'show']);
    Route::post('/services', [AdminServiceController::class, 'store']);
    Route::put('/services/{id}', [AdminServiceController::class, 'update']);
    Route::delete('/services/{id}', [AdminServiceController::class, 'destroy']);
    // Tìm kiếm bệnh nhân khi tạo lịch (Staff cũng dùng)
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::get('/patients/{id}/history', [PatientController::class, 'getHistory']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    //API QUẢN LÝ BỆNH ÁN
    //Lấy danh sách / Tìm kiếm Bệnh án
    Route::get('/medical-records', [MedicalRecordController::class, 'index']);

    //Lấy chi tiết 1 Bệnh án
    Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show']);
    // Quản lý Chuyên khoa
    Route::post('/specialties', [AdminSpecialtyController::class, 'store']);
    Route::put('/specialties/{id}', [AdminSpecialtyController::class, 'update']);
    Route::delete('/specialties/{id}', [AdminSpecialtyController::class, 'destroy']);
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
});


//STAFF (Nhân Viên)
// Yêu cầu: Đã đăng nhập VÀ (Role là 'NhanVien' HOẶC 'QuanTriVien')
Route::middleware(['auth:sanctum', 'role:NhanVien,QuanTriVien'])->prefix('staff')->group(function () {

    //Lấy số liệu thống kê
    Route::get('/dashboard-stats', [StaffDashboardController::class, 'index']);

    //Lấy lịch hẹn chờ xác nhận
    Route::get('/pending-appointments', [AppointmentController::class, 'getPendingAppointments']);

    //Lấy phản hồi (feedback)
    // (Dùng chung API với Admin)
    Route::get('/feedbacks', [FeedbackController::class, 'index']);

    //Lấy danh sách TẤT CẢ lịch hẹn
    // (Dùng chung API với Admin)
    Route::get('/all-appointments', [AppointmentManagementController::class, 'index']);

    //Tạo lịch hẹn mới (thay mặt bệnh nhân)
    Route::post('/appointments', [AppointmentController::class, 'staffCreateAppointment']);

    //Cập nhật trạng thái (Pending -> Confirmed)
    Route::patch('/appointments/{id}/confirm', [AppointmentController::class, 'confirmAppointment']);

    //Cập nhật/sửa thông tin chi tiết lịch hẹn
    Route::put('/appointments/{id}', [AppointmentController::class, 'staffUpdateAppointment']);

    //Hủy lịch (bonus)
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'staffCancelAppointment']);

    //Thêm slot rảnh mới cho bác sĩ
    Route::post('/availability', [DoctorAvailabilityController::class, 'staffStore']);

    //Xóa slot rảnh của bác sĩ
    Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'staffDestroy']);
    // API để Staff "Check-in" cho Bệnh nhân khi họ đến
    Route::patch('/appointments/{id}/check-in', [AppointmentController::class, 'checkInAppointment']);
    // API để Staff Sửa một slot rảnh
    Route::put('/availability/{id}', [DoctorAvailabilityController::class, 'staffUpdate']);
});
