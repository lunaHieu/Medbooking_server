<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $primaryKey = 'UserID';
    public $timestamps = true;
    protected $table = 'users';
    protected $keyType = 'int';
    
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
        return $this->hasOne(Doctor::class, 'UserID', 'UserID')->with('specialty');
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

    // Các quan hệ khác giữ nguyên...
    
    /**
     * Lấy specialty từ doctor (nếu có)
     */
    public function getSpecialtyAttribute()
    {
        if ($this->doctorProfile && $this->doctorProfile->specialty) {
            return $this->doctorProfile->specialty;
        }
        return null;
    }

    /**
     * Format user data for API response
     */
    public function toApiArray()
    {
        $data = [
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

        // Thêm thông tin doctor nếu có
        if ($this->doctorProfile) {
            $data['doctor'] = [
                'DoctorID' => $this->doctorProfile->DoctorID,
                'SpecialtyID' => $this->doctorProfile->SpecialtyID,
                'Degree' => $this->doctorProfile->Degree,
                'YearsOfExperience' => $this->doctorProfile->YearsOfExperience,
                'ProfileDescription' => $this->doctorProfile->ProfileDescription,
                'imageURL' => $this->doctorProfile->imageURL,
            ];
            
            if ($this->doctorProfile->specialty) {
                $data['doctor']['specialty'] = [
                    'SpecialtyID' => $this->doctorProfile->specialty->SpecialtyID,
                    'SpecialtyName' => $this->doctorProfile->specialty->SpecialtyName,
                ];
            }
        }

        return $data;
    }
}