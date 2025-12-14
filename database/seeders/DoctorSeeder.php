<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy UserID của bác sĩ từ Username (an toàn hơn hardcode)
        $bacSiAId = DB::table('users')->where('Username', 'bacsia')->value('UserID');
        $bacSiBId = DB::table('users')->where('Username', 'bacsib')->value('UserID');

        DB::table('doctors')->insert([
            [
                'DoctorID' => $bacSiAId,
                'SpecialtyID' => 1, // Khoa Tim Mạch
                'Degree' => 'Thạc sĩ, Bác sĩ',
                'YearsOfExperience' => 10,
                'ProfileDescription' => 'Kinh nghiệm 10 năm tại Bệnh viện Bạch Mai.',
                'imageURL' => 'https://placehold.co/600x400/FF33A1/FFF?text=Bac+Si+A',
            ],
            [
                'DoctorID' => $bacSiBId,
                'SpecialtyID' => 3, // Khoa Da Liễu
                'Degree' => 'Bác sĩ Chuyên khoa II',
                'YearsOfExperience' => 15,
                'ProfileDescription' => 'Chuyên gia về mụn và các bệnh lý da liễu.',
                'imageURL' => 'https://placehold.co/600x400/33FFA1/FFF?text=Bac+Si+B',
            ],
        ]);
    }
}