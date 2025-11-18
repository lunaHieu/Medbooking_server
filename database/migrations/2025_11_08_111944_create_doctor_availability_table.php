<?php
// Tên file: ..._create_doctor_availability_table.php

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
        Schema::create('doctor_availability', function (Blueprint $table) {
            $table->id('SlotID'); // int auto_increment primary key

            // Khóa ngoại liên kết với Bác sĩ
            $table->unsignedBigInteger('DoctorID');
            $table->foreign('DoctorID')
                  ->references('DoctorID')->on('doctors')
                  ->onDelete('cascade'); // <-- Xóa slot nếu bác sĩ bị xóa

            // Kiểu dữ liệu "dateTime" để lưu cả ngày và giờ
            $table->dateTime('StartTime'); // Ví dụ: 2025-11-10 09:00:00
            $table->dateTime('EndTime');   // Ví dụ: 2025-11-10 09:30:00

            // Trạng thái của slot, mặc định là 'Available' (Còn trống)
            $table->string('Status', 50)->default('Available');

            // Bảng này nên có timestamps để biết slot được tạo/cập nhật khi nào
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_availability');
    }
};