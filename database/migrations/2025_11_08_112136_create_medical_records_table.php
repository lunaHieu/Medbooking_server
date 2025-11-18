<?php
// Tên file: ..._create_medical_records_table.php

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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id('RecordID'); // MaHoSo

            // --- Các Khóa Ngoại ---

            // 1. Liên kết với Bệnh nhân
            $table->unsignedBigInteger('PatientID');
            $table->foreign('PatientID')
                  ->references('UserID')->on('users')
                  ->onDelete('cascade'); // Xóa hồ sơ nếu bệnh nhân bị xóa

            // 2. Liên kết với Bác sĩ (người tạo hồ sơ)
            // Cho phép null, vì bác sĩ có thể bị xóa khỏi hệ thống
            $table->unsignedBigInteger('DoctorID')->nullable(); 
            $table->foreign('DoctorID')
                  ->references('DoctorID')->on('doctors')
                  ->onDelete('set null'); // Giữ lại hồ sơ, chỉ set Bác sĩ = NULL

            // 3. Liên kết với Lịch khám (quan trọng)
            // Cột này là UNIQUE, vì 1 Lịch khám chỉ tạo ra 1 Hồ sơ
            $table->unsignedBigInteger('AppointmentID')->unique(); // <-- Khái niệm mới!
            $table->foreign('AppointmentID')
                  ->references('AppointmentID')->on('appointments')
                  ->onDelete('cascade'); // Xóa hồ sơ nếu lịch khám bị xóa

            // --- Thông tin Hồ sơ ---
            $table->text('Diagnosis')->nullable(); // ChanDoan
            $table->text('Notes')->nullable(); // GhiChu

            // Tự động tạo 'created_at' (thay cho NgayTao) và 'updated_at'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};