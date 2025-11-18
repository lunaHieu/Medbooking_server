<?php
// Tên file: ..._create_specialties_table.php

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
        // Tạo bảng Chuyên Khoa
        Schema::create('specialties', function (Blueprint $table) {
            $table->id('SpecialtyID'); // int auto_increment primary key
            $table->string('SpecialtyName', 255); // varchar(255) not null
            $table->text('Description')->nullable(); // text, cho phép null
            
            // Laravel tự động thêm created_at và updated_at
            // nếu chúng ta không gọi $table->timestamps()
            // Trong trường hợp này, chúng ta không cần timestamps cho bảng danh mục
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialties');
    }
};