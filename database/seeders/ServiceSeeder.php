<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
       // DB::table('services')->truncate(); // Xóa cũ

        // Dữ liệu mẫu (Có link ảnh placeholder)
        DB::table('services')->insert([
            [
                'SpecialtyID' => 1, // Tim Mạch
                'ServiceName' => 'Khám Tim Mạch Tổng Quát',
                'Description' => 'Khám lâm sàng, đo huyết áp.',
                'EstimatedDuration' => 30,
                'Price' => 500000,
                'imageURL' => 'https://placehold.co/600x400/FF5733/FFF?text=Tim+Mach'
            ],
            [
                'SpecialtyID' => 2, // Tiêu Hóa
                'ServiceName' => 'Nội Soi Dạ Dày',
                'Description' => 'Nội soi không đau.',
                'EstimatedDuration' => 60,
                'Price' => 1500000,
                'imageURL' => 'https://placehold.co/600x400/33FF57/FFF?text=Noi+Soi'
            ],
            [
                'SpecialtyID' => 3, // Da Liễu
                'ServiceName' => 'Trị Mụn Laser',
                'Description' => 'Công nghệ Laser CO2.',
                'EstimatedDuration' => 45,
                'Price' => 800000,
                'imageURL' => 'https://placehold.co/600x400/3357FF/FFF?text=Da+Lieu'
            ]
        ]);
    }
}