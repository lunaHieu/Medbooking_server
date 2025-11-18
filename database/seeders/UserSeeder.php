<?php
// Tên file: database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;   // <-- Thêm
use Illuminate\Support\Facades\Hash; // <-- Thêm (để băm mật khẩu)
use App\Models\User;                 // <-- Thêm

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa dữ liệu cũ (nếu có) để làm sạch
      //  User::truncate();

        // Tạo 2 Bác sĩ mẫu
        DB::table('users')->insert([
            [
                'FullName' => 'Bác sĩ Trần Văn A',
                'Username' => 'bacsia',
                'PhoneNumber' => '0911111111',
                'password' => Hash::make('password'), // Mật khẩu chung là 'password'
                'Role' => 'BacSi',
                'Status' => 'HoatDong',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'FullName' => 'Bác sĩ Nguyễn Thị B',
                'Username' => 'bacsib',
                'PhoneNumber' => '0922222222',
                'password' => Hash::make('password'),
                'Role' => 'BacSi',
                'Status' => 'HoatDong',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Tạo 1 Bệnh nhân mẫu (để bạn đăng nhập và test)
        DB::table('users')->insert([
            [
                'FullName' => 'Bệnh nhân Văn Hiếu',
                'Username' => 'hieutest', // Dùng user này để login
                'PhoneNumber' => '0987654321',
                'password' => Hash::make('123456'), // Mật khẩu là '123456'
                'Role' => 'BenhNhan',
                'Status' => 'HoatDong',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Tạo 1 Quản trị viên mẫu
        DB::table('users')->insert([
            [
                'FullName' => 'Admin',
                'Username' => 'admin', // Dùng user này để login Admin
                'PhoneNumber' => '0555555555',
                'password' => Hash::make('admin123'), // Mật khẩu là 'admin123'
                'Role' => 'QuanTriVien', // <-- VAI TRÒ ADMIN
                'Status' => 'HoatDong',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // Tạo 1 Nhân viên (Staff) mẫu
        DB::table('users')->insert([
            [
                'FullName' => 'Nhân viên Lễ tân',
                'Username' => 'staff', // Dùng user này để login Staff
                'PhoneNumber' => '0666666666',
                'password' => Hash::make('staff123'), // Mật khẩu là 'staff123'
                'Role' => 'NhanVien', // <-- VAI TRÒ NHÂN VIÊN
                'Status' => 'HoatDong',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}