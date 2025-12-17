<?php
// Tên file: app/Models/Feedback.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';
    protected $primaryKey = 'FeedbackID';


    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Bệnh nhân (User) đã viết Đánh giá này.
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'PatientID');
    }

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Lịch khám (Appointment) mà Đánh giá này liên kết tới.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'AppointmentID', 'AppointmentID');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'PatientID', 'UserID');
    }

    // Hàm này sẽ tự động tìm sang bảng Doctors nếu TargetType='Doctor'
    public function target()
    {
        return $this->morphTo(__FUNCTION__, 'TargetType', 'TargetID');
    }
}