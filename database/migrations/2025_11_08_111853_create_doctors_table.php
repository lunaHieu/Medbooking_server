<?php
// Tên file: ..._create_doctors_table.php

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
        // Tên bảng: 'doctors'
        Schema::create('doctors', function (Blueprint $table) {
            
            // --- KHÓA 1: LIÊN KẾT VỚI USERS ---

            // ĐÂY LÀ DÒNG QUAN TRỌNG:
            // Nó PHẢI là "unsignedBigInteger" để khớp với "$table->id()"
            $table->unsignedBigInteger('DoctorID'); 
            
            $table->primary('DoctorID'); // Set làm Khóa Chính
            
            $table->foreign('DoctorID')
                  ->references('UserID')->on('users') // Tham chiếu đến 'UserID' trên bảng 'users'
                  ->onDelete('cascade');

            // --- KHÓA 2: LIÊN KẾT VỚI SPECIALTIES ---

            $table->unsignedBigInteger('SpecialtyID');
            $table->foreign('SpecialtyID')
                  ->references('SpecialtyID')->on('specialties');

            // --- THÔNG TIN THÊM ---
            $table->string('Degree', 100)->nullable();
            $table->integer('YearsOfExperience')->nullable();
            $table->text('ProfileDescription')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};