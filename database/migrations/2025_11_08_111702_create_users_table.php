<?php
// Tên file: ..._create_users_table.php (CODE ĐÚNG)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tên bảng: 'users'
        Schema::create('users', function (Blueprint $table) {
            
            // Dòng quan trọng nhất:
            // $table->id() sẽ tạo cột 'id'.
            // $table->id('UserID') sẽ tạo cột 'UserID'.
            // Đây chính là sửa lỗi!
            $table->id('UserID'); 

            // Thêm lại tất cả các cột đã thiếu
            $table->string('FullName', 255);
            $table->date('DateOfBirth')->nullable();
            $table->string('Gender', 10)->nullable();
            $table->string('PhoneNumber', 15)->unique();
            $table->string('Email')->unique()->nullable();
            $table->string('Address', 500)->nullable();
            $table->string('Username', 100)->unique();
            
            // Đổi tên 'PasswordHash' thành 'password' theo chuẩn Laravel
            // Laravel sẽ tự động hash cho chúng ta
            $table->string('password'); 
            
            $table->string('Role', 50);
            $table->string('Status', 50)->default('HoatDong');
            
            // Cột này dùng cho "Remember me" khi login
            $table->rememberToken(); 
            
            $table->timestamps(); // Tự động tạo created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};