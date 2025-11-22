<?php
// Tên file: database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// 1. Thêm 2 dòng "use" này
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 2. Bảo MySQL "tạm thời nhắm mắt lại"
        Schema::disableForeignKeyConstraints();

        // 3. Dọn dẹp các bảng (Làm rỗng)
        // PHẢI dọn theo thứ tự: Bảng con trước, bảng cha sau
        DB::table('appointments')->truncate();
        DB::table('doctor_availability')->truncate();
        DB::table('doctors')->truncate();
        DB::table('services')->truncate();
        DB::table('specialties')->truncate();
        DB::table('users')->truncate();
        DB::table('personal_access_tokens')->truncate();
        // (Chúng ta bỏ qua các bảng còn lại vì chúng sẽ được dọn dẹp khi
        // các bảng chính bị dọn dẹp, hoặc chúng ta sẽ thêm vào sau)

        // 4. Bảo MySQL "mở mắt ra" (bật lại)
        Schema::enableForeignKeyConstraints();

        // 5. Gọi các Seeder để chèn dữ liệu mới
        $this->call([
            UserSeeder::class,           // 1. Tạo User trước
            SpecialtySeeder::class,      // 2. Tạo Chuyên khoa
            DoctorSeeder::class,         // 3. Tạo Bác sĩ (cần UserID và SpecialtyID)
            DoctorAvailabilitySeeder::class, // 4. Tạo Slot (cần DoctorID)
            ServiceSeeder::class,        // 5. Tạo Dịch vụ
        ]);
    }
}