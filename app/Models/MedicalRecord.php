<?php
// Tên file: app/Models/MedicalRecord.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $table = 'medical_records';
    protected $primaryKey = 'RecordID';

    /**
     * Bảng này có dùng timestamps.
     */

    /**
     * === Relationships ===
     */

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Bệnh nhân (User) sở hữu Hồ sơ này.
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'PatientID');
    }

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Bác sĩ (Doctor) đã tạo Hồ sơ này.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'DoctorID');
    }

    /**
     * Mối quan hệ 1-1 (Ngược):
     * Lấy Lịch khám (Appointment) đã tạo ra Hồ sơ này.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'AppointmentID');
    }

    /**
     * Mối quan hệ 1-N:
     * Lấy tất cả các Kết quả khám (ExamResult) của Hồ sơ này.
     */
    public function examResults()
    {
        // "Một Hồ sơ CÓ NHIỀU Kết quả khám"
        return $this->hasMany(ExamResult::class, 'RecordID');
    }
}