<?php
// Tên file: database/seeders/DoctorAvailabilitySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;   // <-- Thêm
use App\Models\DoctorAvailability; // <-- Thêm
use Carbon\Carbon;                   // <-- Thêm (để xử lý thời gian)

class DoctorAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
       // DoctorAvailability::truncate(); // Xóa dữ liệu cũ

        $tomorrow = Carbon::tomorrow(); // Lấy ngày mai

        // Tạo 3 slots cho Bác sĩ 1 (Tim Mạch) vào 9h, 10h, 11h ngày mai
        DB::table('doctor_availability')->insert([
            [
                'DoctorID' => 1,
                'StartTime' => $tomorrow->copy()->setHour(9), // 9:00 ngày mai
                'EndTime' => $tomorrow->copy()->setHour(9)->addMinutes(30), // 9:30
                'Status' => 'Available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'DoctorID' => 1,
                'StartTime' => $tomorrow->copy()->setHour(10), // 10:00 ngày mai
                'EndTime' => $tomorrow->copy()->setHour(10)->addMinutes(30), // 10:30
                'Status' => 'Available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Tạo 1 slot cho Bác sĩ 2 (Da Liễu) vào 14h ngày mai
            [
                'DoctorID' => 2,
                'StartTime' => $tomorrow->copy()->setHour(14), // 14:00 ngày mai
                'EndTime' => $tomorrow->copy()->setHour(14)->addMinutes(30), // 14:30
                'Status' => 'Available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}