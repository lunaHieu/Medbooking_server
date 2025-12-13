<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $table = 'medical_records';
    protected $primaryKey = 'RecordID';

    // THÊM DÒNG NÀY LÀ HẾT LỖI 500 NGAY LẬP TỨC!!!
    protected $fillable = [
        'AppointmentID',
        'PatientID',
        'DoctorID',
        'Diagnosis',
        'Notes'
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'PatientID');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'DoctorID');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'AppointmentID');
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class, 'RecordID');
    }
}