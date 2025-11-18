<?php
// Tên file: ..._create_notifications_table.php

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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('NotificationID'); // MaThongBao

            // --- Các Khóa Ngoại ---

            // 1. Liên kết với Người dùng (Người nhận thông báo)
            $table->unsignedBigInteger('UserID');
            $table->foreign('UserID')
                  ->references('UserID')->on('users')
                  ->onDelete('cascade'); // Xóa thông báo nếu người dùng bị xóa

            // 2. Liên kết với Lịch khám (Nullable)
            $table->unsignedBigInteger('AppointmentID')->nullable(); // Cho phép null
            $table->foreign('AppointmentID')
                  ->references('AppointmentID')->on('appointments')
                  ->onDelete('cascade'); // Xóa thông báo nếu lịch khám bị xóa

            // --- Thông tin Thông báo ---
            
            $table->string('NotificationType', 100)->nullable(); // 'Reminder', 'Confirmation'...
            $table->text('Content'); // NoiDung
            $table->string('Channel', 50)->nullable(); // 'SMS', 'Email', 'AppPush'
            
            // Trạng thái: 'Sent', 'Failed', 'Read'
            $table->string('Status', 50)->default('Sent'); 

            // Tự động tạo 'created_at' (thay cho ThoiGianGui) và 'updated_at'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};