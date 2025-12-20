<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = 'doctors';

    // Khóa chính là DoctorID
    protected $primaryKey = 'DoctorID';

    //DoctorID không tự tăng (nó lấy theo UserID)
    public $incrementing = false;

    protected $fillable = [
        'DoctorID',
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
        return $this->belongsTo(User::class, 'DoctorID', 'UserID');
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'DoctorID', 'DoctorID');
    }
}