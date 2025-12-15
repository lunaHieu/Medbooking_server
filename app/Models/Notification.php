<?php
// Tên file: app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    protected $primaryKey = 'NotificationID';
    protected $fillable = [
        'UserID',
        'Title',
        'Content',
        'NotificationType',
        'Channel',
        'Status'
    ];
    /**
     * Bảng này có dùng timestamps.
     */

    /**
     * === Relationships ===
     */

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Người dùng (User) nhận Thông báo này.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID');
    }

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Lịch khám (Appointment) liên quan (nếu có).
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'AppointmentID');
    }
}