<?php
// Tên file: ..._create_feedbacks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id('FeedbackID'); // MaDanhGia

            // --- Các Khóa Ngoại ---

            // 1. Liên kết với Bệnh nhân (Người viết đánh giá)
            $table->unsignedBigInteger('PatientID');
            $table->foreign('PatientID')
                ->references('UserID')->on('users')
                ->onDelete('cascade'); // Xóa feedback nếu bệnh nhân bị xóa

            // 2. Liên kết với Lịch khám (Đánh giá này cho lịch khám nào)
            $table->unsignedBigInteger('AppointmentID')->nullable();
            $table->foreign('AppointmentID')
                ->references('AppointmentID')->on('appointments')
                ->onDelete('cascade'); // Xóa feedback nếu lịch khám bị xóa

            // --- Thông tin Đánh giá ---

            // Cột này để lưu loại đối tượng: 'Doctor' hay 'Service'
            $table->string('TargetType', 50);

            // Cột này để lưu ID của đối tượng: (ví dụ: DoctorID=5, ServiceID=2)
            $table->unsignedBigInteger('TargetID');

            $table->integer('Rating'); // Số sao (1-5)
            $table->text('Comment')->nullable(); // NhanXet

            // Tự động tạo 'created_at' (thay cho NgayDanhGia) và 'updated_at'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};