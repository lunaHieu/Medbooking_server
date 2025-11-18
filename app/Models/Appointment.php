<?php
// Tên file: app/Models/Appointment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';
    protected $primaryKey = 'AppointmentID';

    /**
     * Bảng này có dùng timestamps.
     */

    /**
     * === Relationships ===
     */

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Bệnh nhân (User) của Lịch khám này.
     */
    public function patient()
    {
        // "Một Lịch khám THUỘC VỀ một Bệnh nhân (User)"
        return $this->belongsTo(User::class, 'PatientID');
    }

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Bác sĩ (Doctor) của Lịch khám này.
     */
    public function doctor()
    {
        // "Một Lịch khám THUỘC VỀ một Bác sĩ (Doctor)"
        return $this->belongsTo(Doctor::class, 'DoctorID');
    }

    /**
     * Mối quan-hệ N-1 (Ngược):
     * Lấy Dịch vụ (Service) được đặt (nếu có).
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'ServiceID');
    }

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Slot thời gian đã đặt (nếu có).
     */
    public function slot()
    {
        return $this->belongsTo(DoctorAvailability::class, 'SlotID');
    }

    /**
     * Mối quan hệ 1-1:
     * Lấy Hồ sơ bệnh án (MedicalRecord) được tạo ra từ Lịch khám này.
     */
    public function medicalRecord()
    {
        // "Một Lịch khám CÓ MỘT Hồ sơ bệnh án"
        return $this->hasOne(MedicalRecord::class, 'AppointmentID');
    }

    /**
     * Mối quan hệ 1-N:
     * Lấy các Đánh giá (Feedbacks) cho Lịch khám này.
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'AppointmentID');
    }

    /**
     * Mối quan hệ 1-N:
     * Lấy các Thông báo (Notifications) liên quan đến Lịch khám này.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'AppointmentID');
    }
}