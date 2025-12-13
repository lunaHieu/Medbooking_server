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
// --- CÁC ROUTE ĐƯỢC BẢO VỆ (Bắt buộc phải có "chìa khóa" - Token) ---
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

    // === THÊM ROUTE MỚI CỦA BẠN VÀO ĐÂY ===
    // API để tạo một lịch hẹn mới
    Route::post('/appointments', [AppointmentController::class, 'store']);

    //API de dang xuat
    Route::post('logout', [AuthController::class, 'logout']);

    //API de benh nhan tu huy lich 
    //{id} la id lich hen
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);

    // API để Bệnh nhân tự tải ảnh đại diện
    // (Chúng ta sẽ dùng AuthController cho tiện)
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
});

// --- CÁC ROUTE KHÔNG BẢO VỆ (Ai cũng có thể gọi được) ---
// 2. Định nghĩa route đầu tiên của chúng ta
// Khi ai đó gọi GET /api/specialties...
// ...hãy chạy hàm 'index' trong SpecialtyController
Route::get('/specialties', [SpecialtyController::class, 'index']);

// 2. Route cho Đăng ký (Register)
Route::post('/register', [AuthController::class, 'register']);

// 3. Route cho Đăng nhập (Login)
Route::post('/login', [AuthController::class, 'login']);

Route::get('/doctors', [DoctorController::class, 'index']);
// {id} là một "tham số" (parameter) mà Laravel sẽ tự động bắt lấy
// Nó sẽ chạy hàm 'getAvailability' trong DoctorController
Route::get('/doctors/{id}/availability', [DoctorController::class, 'getAvailability']);


// {id} là "tham số"
// Nó sẽ chạy hàm 'show' trong DoctorController
// 'show' là tên quy ước của Laravel cho "hiển thị 1 cái"
Route::get('/doctors/{id}', [DoctorController::class, 'show']);
// Lấy lịch trống theo Chuyên khoa
// URL: GET /api/specialties/{id}/availability
Route::get('/specialties/{id}/availability', [SpecialtyController::class, 'getAvailability']);

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/specialties/{id}', [SpecialtyController::class, 'show']);
//Gọi ra 3 feedbacks tốt nhất dựa theo số sao
Route::get('/top-feedbacks', [FeedbackController::class, 'getTopFeedbacks']);
//Nhom 3 :cac route cua bac si(dotor) 
//2 lop bao ve 
//1. auth:sanctum
//2. role:dotor
Route::middleware(['auth:sanctum', 'role:BacSi'])->prefix('doctor')->group(function () {

    // API để Bác sĩ tự tạo lịch trống
    // URL sẽ là: POST /api/doctor/availability
    Route::post('/availability', [App\Http\Controllers\Api\DoctorAvailabilityController::class, 'store']);

    // API Bác sĩ xem lịch hẹn đã đặt của mình
    // URL sẽ là: GET /api/doctor/my-schedule
    Route::get('/my-schedule', [AppointmentController::class, 'doctorSchedule']);

    // API Bác sĩ tạo hồ sơ bệnh án
    // URL sẽ là: POST /api/doctor/medical-records
    Route::post('/medical-records', [MedicalRecordController::class, 'store']);

    // API Lấy hàng đợi (queue) - những người đã "CheckedIn"
    // URL: GET /api/doctor/queue
    Route::get('/queue', [AppointmentController::class, 'getDoctorQueue']);
    // API Lấy số liệu Thống kê cho Homepage
    // URL: GET /api/doctor/dashboard-stats
    Route::get('/dashboard-stats', [DoctorDashboardController::class, 'index']);
    // API Bác sĩ Xóa (khóa) một slot rảnh
    // URL: DELETE /api/doctor/availability/{id}
    Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'destroy']);
    // API Bác sĩ xem chi tiết 1 lịch hẹn
    // URL: GET /api/doctor/appointments/{id}
    Route::get('/appointments/{id}', [AppointmentController::class, 'doctorShowAppointment']);
    // API Bác sĩ tải file kết quả (X-quang...) lên 1 Bệnh án
    // {id} là ID của Bệnh án (MedicalRecordID)
    // URL: POST /api/doctor/medical-records/{id}/upload-result
    Route::post('/medical-records/{id}/upload-result', [MedicalRecordController::class, 'uploadResult']);
    // API Bác sĩ xem lại các bệnh án đã tạo
    // URL: GET /api/doctor/my-medical-records
    Route::get('/my-medical-records', [MedicalRecordController::class, 'myMedicalRecords']);
    // API Bác sĩ xem lịch sử của 1 bệnh nhân
    // {patientId} là UserID của Bệnh nhân
    // URL: GET /api/doctor/patient-history/{patientId}
    Route::get('/patient-history/{patientId}', [MedicalRecordController::class, 'getPatientHistory']);
    // API Bác sĩ Sửa bệnh án
    // URL: PUT /api/doctor/medical-records/{id}
    Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update']);
    //API cập nhật trạng thái (Bắt đầu khám -> Hoàn tất)
    Route::put('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
    //API xem danh sách Slot rảnh của chính bác sĩ đó
    Route::get('/my-slots', [DoctorAvailabilityController::class, 'index']);
});

//nhom4 admin
Route::middleware(['auth:sanctum', 'role:QuanTriVien,NhanVien'])->prefix('admin')->group(function () {

    // API để Admin tạo Bác sĩ mới
    // URL sẽ là: POST /api/admin/doctors
    Route::post('/doctors', [DoctorManagementController::class, 'store']);


    //api update bac si
    Route::put('/doctors/{id}', [DoctorManagementController::class, 'update']);

    // API để Admin xem tất cả Lịch hẹn
    // URL sẽ là: GET /api/admin/appointments
    Route::get('/all-appointments', [AppointmentManagementController::class, 'index']);

    //api de admin tai anh len cho bac si
    // URL sẽ là: POST /api/admin/doctors/{id}/upload-image
    Route::post('/doctors/{id}/upload-image', [DoctorManagementController::class, 'uploadImage']);
    // API để Admin Xóa Bác sĩ
    // URL sẽ là: DELETE /api/admin/doctors/{id}
    Route::delete('/doctors/{id}', [DoctorManagementController::class, 'destroy']);
    // API để Admin Xóa Bệnh án
    // URL sẽ là: DELETE /api/admin/medical-records/{id}
    Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy']);

    //QUẢN LÝ THÔNG BÁO
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/notifications/send', [AdminNotificationController::class, 'send']);
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
    //API cho Staff va admin co the dung
    // Yêu cầu 11: Tìm kiếm bệnh nhân khi tạo lịch
    // GET /api/admin/patients (Staff có thể dùng API này)
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::get('/patients/{id}/history', [PatientController::class, 'getHistory']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    // === API QUẢN LÝ BỆNH ÁN (MỚI) ===
    // 1. Lấy danh sách / Tìm kiếm Bệnh án
    // URL: GET /api/admin/medical-records
    Route::get('/medical-records', [MedicalRecordController::class, 'index']);

    // 2. Lấy chi tiết 1 Bệnh án
    // URL: GET /api/admin/medical-records/{id}
    Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show']);
    // (API Tạo/Sửa Patient chúng ta giữ cho Admin)
    // Quản lý Chuyên khoa
    Route::post('/specialties', [AdminSpecialtyController::class, 'store']);
    Route::put('/specialties/{id}', [AdminSpecialtyController::class, 'update']);
    Route::delete('/specialties/{id}', [AdminSpecialtyController::class, 'destroy']);
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
});


//STAFF (Nhân Viên)
// Yêu cầu: Đã đăng nhập VÀ (Role là 'NhanVien' HOẶC 'QuanTriVien')
// (Chúng ta cho cả Admin vào group này để Admin cũng test được)
Route::middleware(['auth:sanctum', 'role:NhanVien,QuanTriVien'])->prefix('staff')->group(function () {

    // Yêu cầu 1: Lấy số liệu thống kê
    // GET /api/staff/dashboard-stats
    Route::get('/dashboard-stats', [StaffDashboardController::class, 'index']);

    // Yêu cầu 2: Lấy lịch hẹn chờ xác nhận
    // GET /api/staff/pending-appointments
    Route::get('/pending-appointments', [AppointmentController::class, 'getPendingAppointments']);

    // Yêu cầu 3: Lấy phản hồi (feedback)
    // (Dùng chung API với Admin)
    // GET /api/staff/feedbacks
    Route::get('/feedbacks', [FeedbackController::class, 'index']);

    // Yêu cầu 4: Lấy danh sách TẤT CẢ lịch hẹn
    // (Dùng chung API với Admin)
    // GET /api/staff/all-appointments
    Route::get('/all-appointments', [AppointmentManagementController::class, 'index']);

    // Yêu cầu 5: Tạo lịch hẹn mới (thay mặt bệnh nhân)
    // POST /api/staff/appointments
    Route::post('/appointments', [AppointmentController::class, 'staffCreateAppointment']);

    // Yêu cầu 6: Cập nhật trạng thái (Pending -> Confirmed)
    // PATCH /api/staff/appointments/{id}/confirm
    Route::patch('/appointments/{id}/confirm', [AppointmentController::class, 'confirmAppointment']);

    // Yêu cầu 7: Cập nhật/sửa thông tin chi tiết lịch hẹn
    // PUT /api/staff/appointments/{id}
    Route::put('/appointments/{id}', [AppointmentController::class, 'staffUpdateAppointment']);

    // Yêu cầu 8: Hủy lịch (bonus)
    // PATCH /api/staff/appointments/{id}/cancel
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'staffCancelAppointment']);

    // Yêu cầu 9: Thêm slot rảnh mới cho bác sĩ
    // POST /api/staff/availability
    Route::post('/availability', [DoctorAvailabilityController::class, 'staffStore']);

    // Yêu cầu 10: Xóa slot rảnh của bác sĩ
    // DELETE /api/staff/availability/{id}
    Route::delete('/availability/{id}', [DoctorAvailabilityController::class, 'staffDestroy']);
    // API để Staff "Check-in" cho Bệnh nhân khi họ đến
    // URL sẽ là: PATCH /api/staff/appointments/{id}/check-in
    Route::patch('/appointments/{id}/check-in', [AppointmentController::class, 'checkInAppointment']);
    // API để Staff Sửa một slot rảnh
    // URL sẽ là: PUT /api/staff/availability/{id}
    Route::put('/availability/{id}', [DoctorAvailabilityController::class, 'staffUpdate']);
});
