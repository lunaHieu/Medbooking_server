<?php
// Tên file: app/Models/User.php

namespace App\Models;

// Thêm 2 dòng 'use' này để dùng cho API và Xác thực
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Class 'User' kế thừa từ 'Authenticatable' (để có thể login)
class User extends Authenticatable
{
    // 'HasApiTokens' là của Laravel Sanctum (để tạo API token)
    use HasApiTokens, Notifiable;

    /**
     * Dòng quan trọng nhất:
     * Báo cho Laravel biết khóa chính của bảng 'users' là 'UserID',
     * chứ không phải 'id' (mặc định của Laravel).
     * Nếu thiếu dòng này, mọi thứ sẽ lỗi!
     */
    protected $primaryKey = 'UserID';
    // === THÊM MẢNG (ARRAY) NÀY VÀO ===
    /**
     * Các thuộc tính (attributes) có thể được Gán hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'FullName',
        'Username',
        'PhoneNumber',
        'Email',
        'Address',
        'DateOfBirth',
        'Gender',
        'password', // (Chúng ta hash nó trước, nhưng nó cần ở đây)
        'Role',
        'Status',
        'avatar_url',
    ];
    /**
     * Các cột này sẽ bị "che giấu" (không hiển thị)
     * khi chúng ta trả Model này về dạng API/JSON (để bảo mật).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * === ĐỊNH NGHĨA CÁC MỐI QUAN HỆ (Relationships) ===
     * Đây là phần "kỳ diệu" của Eloquent
     */

    /**
     * Mối quan hệ 1-1:
     * Lấy hồ sơ bác sĩ (Doctor profile) của User này.
     */
    public function doctorProfile()
    {
        // "Một User CÓ MỘT Doctor profile, liên kết bằng khóa ngoại 'DoctorID'"
        // (Khóa ngoại 'DoctorID' nằm trên bảng 'doctors')
        return $this->hasOne(Doctor::class, 'UserID', 'UserID');
    }

    /**
     * Mối quan hệ 1-N (Một-Nhiều):
     * Lấy tất cả các Lịch khám (Appointments) mà User này đặt (với tư cách Bệnh nhân).
     */
    public function appointmentsAsPatient()
    {
        // "Một User CÓ NHIỀU Appointments, liên kết bằng khóa ngoại 'PatientID'"
        return $this->hasMany(Appointment::class, 'PatientID');
    }

    /**
     * Mối quan hệ 1-N (Một-Nhiều):
     * Lấy tất cả các Hồ sơ bệnh án (MedicalRecords) của User này.
     */
    public function medicalRecords()
    {
        // "Một User CÓ NHIỀU MedicalRecords, liên kết bằng khóa ngoại 'PatientID'"
        return $this->hasMany(MedicalRecord::class, 'PatientID');
    }

    /**
     * Mối quan hệ 1-N (Một-Nhiều):
     * Lấy tất cả các Đánh giá (Feedbacks) mà User này đã viết.
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'PatientID');
    }

    /**
     * Mối quan hệ 1-N (Một-Nhiều):
     * Lấy tất cả các Thông báo (Notifications) của User này.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'UserID');
    }
    /**
     * Lấy danh sách người thân để liên kết
     *
     */
    public function familyMembers()
    {
        return $this->belongsToMany(
            User::class,
            'user_relations',
            'UserID',
            'RelativeUserID'
        )
            ->withPivot('RelationType')
            ->withTimestamps();
    }
}