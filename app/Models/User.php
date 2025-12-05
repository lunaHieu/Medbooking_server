<?php
// Tên file: app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $primaryKey = 'UserID';
    
    protected $fillable = [
        'FullName',
        'Username',
        'PhoneNumber',
        'Email',
        'Address',
        'DateOfBirth',
        'Gender',
        'password',
        'Role',
        'Status',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * === ĐỊNH NGHĨA CÁC MỐI QUAN HỆ (Relationships) ===
     */

    public function doctorProfile()
    {
        return $this->hasOne(Doctor::class, 'UserID', 'UserID');
    }

    public function appointmentsAsPatient()
    {
        return $this->hasMany(Appointment::class, 'PatientID', 'UserID');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'PatientID', 'UserID');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'PatientID', 'UserID');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'UserID', 'UserID');
    }

    // ======== THÊM CÁC QUAN HỆ MỚI ========

    /**
     * Lấy appointments với tư cách bác sĩ
     */
    public function appointmentsAsDoctor()
    {
        return $this->hasManyThrough(
            Appointment::class,
            Doctor::class,
            'UserID',       // Foreign key on doctors table
            'DoctorID',     // Foreign key on appointments table  
            'UserID',       // Local key on users table
            'DoctorID'      // Local key on doctors table
        );
    }

    /**
     * Lấy medical records với tư cách bác sĩ
     */
    public function medicalRecordsAsDoctor()
    {
        return $this->hasManyThrough(
            MedicalRecord::class,
            Doctor::class,
            'UserID',       // Foreign key on doctors table
            'DoctorID',     // Foreign key on medical_records table  
            'UserID',       // Local key on users table
            'DoctorID'      // Local key on doctors table
        );
    }

    /**
     * Phương thức hỗ trợ để kiểm tra role
     */
    public function isDoctor()
    {
        return $this->Role === 'BacSi';
    }

    public function isPatient()
    {
        return $this->Role === 'BenhNhan';
    }

    public function isAdmin()
    {
        return $this->Role === 'QuanTriVien';
    }

    public function isStaff()
    {
        return $this->Role === 'NhanVien';
    }

    /**
     * Get the username field for authentication
     */
    public function username()
    {
        return 'Username';
    }

    /**
     * Format user data for API response
     */
    public function toApiArray()
    {
        return [
            'UserID' => $this->UserID,
            'FullName' => $this->FullName,
            'Email' => $this->Email,
            'PhoneNumber' => $this->PhoneNumber,
            'Role' => $this->Role,
            'Status' => $this->Status,
            'DateOfBirth' => $this->DateOfBirth,
            'Gender' => $this->Gender,
            'Address' => $this->Address,
            'avatar_url' => $this->avatar_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}