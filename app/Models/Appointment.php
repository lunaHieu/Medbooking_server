<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';
    protected $primaryKey = 'AppointmentID';

    // Các trường được phép mass assignment
    protected $fillable = [
        'PatientID',
        'DoctorID',
        'ServiceID',
        'SlotID',
        'StartTime',
        'Status',
        'InitialSymptoms',
        'CancellationReason',
        'Type'
    ];

    /**
     * === Relationships ===
     */

    public function patient()
    {
        return $this->belongsTo(User::class, 'PatientID', 'UserID');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'DoctorID', 'DoctorID');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'ServiceID', 'ServiceID');
    }

    /**
     * [QUAN TRỌNG] Đổi tên từ 'slot' thành 'schedule' 
     * để khớp với câu lệnh with('schedule') trong Controller
     */
    public function schedule()
    {
        return $this->belongsTo(DoctorAvailability::class, 'SlotID', 'SlotID');
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class, 'AppointmentID', 'AppointmentID');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'AppointmentID', 'AppointmentID');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'AppointmentID', 'AppointmentID');
    }
}