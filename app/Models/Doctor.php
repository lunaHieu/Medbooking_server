<?php
// Tên file: app/Models/Doctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    /**
     * Chỉ định tên bảng.
     * (Không bắt buộc vì Laravel tự đoán 'doctors', nhưng viết cho rõ ràng).
     */
    protected $table = 'doctors';

    /**
     * Dòng 1: Chỉ định Khóa Chính.
     */
    protected $primaryKey = 'DoctorID';

    /**
     * Dòng 2: Rất quan trọng!
     * Báo cho Laravel biết rằng Khóa Chính này KHÔNG PHẢI là số tự tăng.
     */
    public $incrementing = false;

    /**
     * Dòng 3: Rất quan trọng!
     * Báo cho Laravel biết Model này KHÔNG sử dụng các cột timestamps
     * (created_at và updated_at).
     */
    public $timestamps = false;

    /**
     * === ĐỊNH NGHĨA CÁC MỐI QUAN HỆ (Relationships) ===
     */

    /**
     * Mối quan hệ 1-1 (Ngược):
     * Lấy thông tin User (họ tên, email...) của Bác sĩ này.
     */
    public function user()
    {
        // "Một Bác sĩ THUỘC VỀ một User, liên kết bằng khóa 'DoctorID'"
        return $this->belongsTo(User::class, 'DoctorID');
    }

    /**
     * Mối quan hệ N-1 (Nhiều-Một):
     * Lấy Chuyên khoa của Bác sĩ này.
     */
    public function specialty()
    {
        // "Một Bác sĩ THUỘC VỀ một Specialty, liên kết bằng khóa 'SpecialtyID'"
        return $this->belongsTo(Specialty::class, 'SpecialtyID');
    }

    /**
     * Mối quan hệ 1-N (Một-Nhiều):
     * Lấy tất cả các "slot" thời gian rảnh của Bác sĩ này.
     */
    public function availabilitySlots()
    {
        // "Một Bác sĩ CÓ NHIỀU DoctorAvailability, liên kết bằng khóa 'DoctorID'"
        return $this->hasMany(DoctorAvailability::class, 'DoctorID');
    }

    /**
     * Mối quan hệ 1-N (Một-Nhiều):
     * Lấy tất cả các Lịch khám (Appointments) của Bác sĩ này.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'DoctorID');
    }
}