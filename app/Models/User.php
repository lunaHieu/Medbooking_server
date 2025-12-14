<?php
<<<<<<< HEAD
// Tên file: app/Models/User.php
=======
>>>>>>> tung-feature-doctor-dashboard

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

<<<<<<< HEAD
    /**
     * === ĐỊNH NGHĨA CÁC MỐI QUAN HỆ (Relationships) ===
     * Đây là phần "kỳ diệu" của Eloquent
     */
=======
    protected $casts = [
        'DateOfBirth' => 'date',
    ];

    // === RELATIONSHIPS ===
>>>>>>> tung-feature-doctor-dashboard

    /**
     * Mối quan hệ 1-1:
     * Lấy hồ sơ bác sĩ (Doctor profile) của User này.
     */
    public function doctorProfile()
    {
<<<<<<< HEAD
        // "Một User CÓ MỘT Doctor profile, liên kết bằng khóa ngoại 'DoctorID'"
        // (Khóa ngoại 'DoctorID' nằm trên bảng 'doctors')
        return $this->hasOne(Doctor::class, 'UserID', 'UserID');
=======
        return $this->hasOne(Doctor::class, 'DoctorID', 'UserID')
                     ->withDefault()
                     ->with('specialty');
>>>>>>> tung-feature-doctor-dashboard
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
<<<<<<< HEAD
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
=======

    public function getSpecialtyAttribute()
    {
        return $this->doctorProfile?->specialty;
    }

    public function toApiArray()
    {
        $data = [
            'UserID' => $this->UserID,
            'FullName' => $this->FullName,
            'Username' => $this->Username,
            'Email' => $this->Email,
            'PhoneNumber' => $this->PhoneNumber,
            'Role' => $this->Role,
            'Status' => $this->Status,
            'DateOfBirth' => $this->DateOfBirth?->format('Y-m-d'),
            'Gender' => $this->Gender,
            'Address' => $this->Address,
            'avatar_url' => $this->avatar_url ? asset('storage/' . $this->avatar_url) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->doctorProfile) {
            $data['doctor'] = [
                'DoctorID' => $this->doctorProfile->DoctorID,
                'SpecialtyID' => $this->doctorProfile->SpecialtyID,
                'Degree' => $this->doctorProfile->Degree,
                'YearsOfExperience' => $this->doctorProfile->YearsOfExperience,
                'ProfileDescription' => $this->doctorProfile->ProfileDescription,
                'imageURL' => $this->doctorProfile->imageURL ? asset('storage/' . $this->doctorProfile->imageURL) : null,
            ];

            if ($this->doctorProfile->specialty) {
                $data['doctor']['specialty'] = [
                    'SpecialtyID' => $this->doctorProfile->specialty->SpecialtyID,
                    'SpecialtyName' => $this->doctorProfile->specialty->SpecialtyName,
                    'Description' => $this->doctorProfile->specialty->Description,
                    'imageURL' => $this->doctorProfile->specialty->imageURL,
                ];
            }
        }

        return $data;
>>>>>>> tung-feature-doctor-dashboard
    }
}