<?php
// Tên file: app/Models/ExamResult.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    protected $table = 'exam_results';
    protected $primaryKey = 'ResultID';

    /**
     * Bảng này có dùng timestamps.
     */

    /**
     * === Relationships ===
     */

    /**
     * Mối quan hệ N-1 (Ngược):
     * Lấy Hồ sơ bệnh án (MedicalRecord) cha của Kết quả này.
     */
    public function medicalRecord()
    {
        // "Một Kết quả THUỘC VỀ một Hồ sơ"
        return $this->belongsTo(MedicalRecord::class, 'RecordID');
    }
}