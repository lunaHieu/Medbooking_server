<?php
// Tên file: ..._create_exam_results_table.php

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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id('ResultID'); // MaKetQua

            // --- Khóa Ngoại ---
            // Liên kết với Hồ sơ bệnh án
            $table->unsignedBigInteger('RecordID');
            $table->foreign('RecordID')
                  ->references('RecordID')->on('medical_records')
                  ->onDelete('cascade'); // <-- Logic quan trọng

            // --- Thông tin File ---
            
            // Dùng string(1000) để lưu đường dẫn file, 
            // vì đường dẫn có thể rất dài
            $table->string('FilePath', 1000); 
            
            $table->string('FileType', 50)->nullable(); // 'PDF', 'PNG', 'DICOM'...
            $table->string('FileDescription', 500)->nullable(); // MoTaFile

            // Tự động tạo 'created_at' (thay cho NgayTaiLen) và 'updated_at'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};