<?php
// Tên file: database/seeders/DoctorSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // <-- Thêm
use App\Models\Doctor;               // <-- Thêm

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        //Doctor::truncate(); // Xóa dữ liệu cũ

        DB::table('doctors')->insert([
            [
                'DoctorID' => 1, // PHẢI khớp với UserID của 'bacsia'
                'SpecialtyID' => 1, // 'Khoa Tim Mạch' (ID=1)
                'Degree' => 'Thạc sĩ, Bác sĩ',
                'YearsOfExperience' => 10,
                'ProfileDescription' => 'Kinh nghiệm 10 năm tại Bệnh viện Bạch Mai.',
                'imageURL' => 'https://example.com/images/bac-si-a.jpg'
            ],
            [
                'DoctorID' => 2, // PHẢI khớp với UserID của 'bacsib'
                'SpecialtyID' => 3, // 'Khoa Da Liễu' (ID=3)
                'Degree' => 'Bác sĩ Chuyên khoa II',
                'YearsOfExperience' => 15,
                'ProfileDescription' => 'Chuyên gia về mụn và các bệnh lý da liễu.',
                'imageURL' => 'https://example.com/images/bac-si-b.jpg'
            ],
        ]);
    }
}