<?php
// Tên file: app/Models/DoctorAvailability.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorAvailability extends Model
{
    use HasFactory;

    protected $table = 'doctor_availability';

    /**
     * Khóa chính.
     */
    protected $primaryKey = 'SlotID';

    /**
     * Bảng này CÓ DÙNG timestamps (created_at, updated_at).
     * Vì vậy, chúng ta không cần viết 'public $timestamps = true;'
     * vì đó là mặc định.
     */

    /**
     * === Relationships ===
     */

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Bác sĩ sở hữu Slot này.
     */
    public function doctor()
    {
        // "Một Slot THUỘC VỀ một Bác sĩ"
        return $this->belongsTo(Doctor::class, 'DoctorID');
    }

    /**
     * Mối quan hệ 1-1:
     * Lấy Lịch khám (Appointment) đã đặt vào Slot này (nếu có).
     */
    public function appointment()
    {
        // "Một Slot CÓ MỘT Lịch khám"
        return $this->hasOne(Appointment::class, 'SlotID');
    }
}