<?php
// Tên file: ..._create_appointments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('AppointmentID'); // MaLich

            // --- Các Khóa Ngoại ---

            // 1. Liên kết với Bệnh nhân (UserID từ bảng Users)
            $table->unsignedBigInteger('PatientID'); // MaBenhNhan
            $table->foreign('PatientID')
                ->references('UserID')->on('users')
                ->onDelete('cascade'); // Xóa lịch hẹn nếu bệnh nhân bị xóa

            // 2. Liên kết với Bác sĩ (DoctorID từ bảng Doctors)
            $table->unsignedBigInteger('DoctorID'); // MaBacSi
            $table->foreign('DoctorID')
                ->references('DoctorID')->on('doctors')
                ->onDelete('cascade'); // Xóa lịch hẹn nếu bác sĩ bị xóa

            // 3. Liên kết với Dịch vụ (Nullable)
            $table->unsignedBigInteger('ServiceID')->nullable(); // MaDichVu (cho phép null)
            $table->foreign('ServiceID')
                ->references('ServiceID')->on('services')
                ->onDelete('set null'); // <-- Khái niệm mới!

            // 4. Liên kết với Slot thời gian (Nullable)
            $table->unsignedBigInteger('SlotID')->nullable(); // MaSlot (cho phép null)
            $table->foreign('SlotID')
                ->references('SlotID')->on('doctor_availability')
                ->onDelete('set null'); // <-- Khái niệm mới!

            // --- Thông tin Lịch hẹn ---

            $table->dateTime('StartTime'); // ThoiGianBatDau
            $table->integer('EstimatedDuration')->nullable(); // ThoiLuongDuKien
            $table->text('InitialSymptoms')->nullable(); // TrieuChungBanDau

            // TrangThai, mặc định là 'Pending' (ChoXacNhan)
            $table->string('Status', 50)->default('Pending');

            $table->text('CancellationReason')->nullable(); // LyDoHuy
            $table->string('Type', 10)->default('New');
            // $table->timestamps() sẽ tự động tạo cột `created_at` (thay cho NgayTao)
            // và cột `updated_at`
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};