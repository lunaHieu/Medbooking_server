<?php
// Tên file: app/Models/Specialty.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use HasFactory;

    protected $table = 'specialties';
    protected $primaryKey = 'SpecialtyID';

    /**
     * Tắt timestamps (created_at, updated_at) 
     * vì bảng này không có.
     */
    public $timestamps = false;

    /**
     * === Relationships ===
     */

    /**
     * Mối quan hệ 1-N:
     * Lấy tất cả Bác sĩ thuộc Chuyên khoa này.
     */

    protected $fillable = [
        'SpecialtyName',
        'imageURL',
    ];
    public function doctors()
    {
        // "Một Chuyên khoa CÓ NHIỀU Bác sĩ"
        return $this->hasMany(Doctor::class, 'SpecialtyID');
    }

    /**
     * Mối quan hệ 1-N:
     * Lấy tất cả Dịch vụ thuộc Chuyên khoa này.
     */
    public function services()
    {
        // "Một Chuyên khoa CÓ NHIỀU Dịch vụ"
        return $this->hasMany(Service::class, 'SpecialtyID');
    }
}