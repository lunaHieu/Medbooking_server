<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = 'doctors';
    protected $primaryKey = 'DoctorID';

    protected $fillable = [
        'UserID',
        'SpecialtyID',
        'Degree',
        'YearsOfExperience',
        'ProfileDescription',
        'imageURL',
    ];

    public $timestamps = false;

    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'SpecialtyID', 'SpecialtyID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }
}
