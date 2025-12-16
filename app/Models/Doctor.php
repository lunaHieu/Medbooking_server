<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = 'doctors';
    
    // Khóa chính là DoctorID
    protected $primaryKey = 'DoctorID';

    // ⚠️ QUAN TRỌNG: Vì DoctorID không tự tăng (nó lấy theo UserID), phải tắt auto-increment
    public $incrementing = false; 

    protected $fillable = [
        'DoctorID', // Phải có cái này để lệnh create(['DoctorID' => ...]) chạy được
        'SpecialtyID',
        'Degree',
        'YearsOfExperience',
        'ProfileDescription',
        'imageURL',
    ];

    public $timestamps = false;

    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'SpecialtyID', 'SpecialtyID');
    }

    public function user()
    {
        // 👇 CHỈNH SỬA QUAN TRỌNG NHẤT:
        // Nói với Laravel: "Hãy lấy User có UserID bằng với DoctorID của tôi"
        return $this->belongsTo(User::class, 'DoctorID', 'UserID');
    }
}