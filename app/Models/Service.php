<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    protected $primaryKey = 'ServiceID';

    public $timestamps = false;


    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Chuyên khoa mà Dịch vụ này thuộc về.
     */
    public function specialty()
    {
        // "Một Dịch vụ THUỘC VỀ một Chuyên khoa"
        return $this->belongsTo(Specialty::class, 'SpecialtyID');
    }

    /**
     * Mối quan hệ 1-N:
     * Lấy tất cả các Lịch khám (Appointments) sử dụng Dịch vụ này.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'ServiceID');
    }
}