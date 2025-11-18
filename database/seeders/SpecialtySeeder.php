<?php
// Tên file: database/seeders/SpecialtySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // <-- Thêm dòng này

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chúng ta sẽ dùng "Query Builder" (DB::table)
        // để chèn dữ liệu. Đây là cách nhanh và đơn giản.
        
        DB::table('specialties')->insert([
            [
                'SpecialtyName' => 'Khoa Tim Mạch',
                'Description' => 'Chuyên điều trị các bệnh lý về tim và mạch máu.',
                'imageURL' => 'https://example.com/images/khoa-tim-mach.jpg'
            ],
            [
                'SpecialtyName' => 'Khoa Tiêu Hóa',
                'Description' => 'Chuyên điều trị các bệnh lý về dạ dày, ruột, gan, mật.',
                'imageURL' => 'https://example.com/images/khoa-tieu-hoa.jpg'
            ],
            [
                'SpecialtyName' => 'Khoa Da Liễu',
                'Description' => 'Chuyên điều trị các bệnh về da, tóc, móng.',
                'imageURL' => 'https://example.com/images/khoa-da-lieu.jpg'
            ],
            [
                'SpecialtyName' => 'Khoa Cơ Xương Khớp',
                'Description' => 'Chuyên điều trị các vấn đề về gân, cơ, xương, khớp.',
                'imageURL' => 'https://example.com/images/khoa-co-xuong-khop.jpg'
            ],
            [
                'SpecialtyName' => 'Khoa Tai Mũi Họng',
                'Description' => 'Chuyên điều trị các bệnh lý tai, mũi, họng.',
                'imageURL' => 'https://example.com/images/khoa-tai-mui-hong.jpg'
            ]
        ]);
    }
}