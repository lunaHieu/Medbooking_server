<?php
// Tên file: ..._create_services_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id('ServiceID'); // int auto_increment primary key
            $table->string('ServiceName', 255);
            $table->text('Description')->nullable();
            $table->integer('EstimatedDuration')->nullable(); // (tính bằng phút)

            // Dùng "decimal" cho tiền tệ.
            // (18, 2) nghĩa là: tổng 18 chữ số, với 2 chữ số sau dấu phẩy, vì khi làm tròn int or float sẽ dễ bị sai
            $table->decimal('Price', 18, 2)->nullable();

            // --- Đây là phần quan trọng: Định nghĩa Khóa Ngoại ---

            // 1. Tạo cột 'SpecialtyID'
            // Kiểu dữ liệu PHẢI là "unsignedBigInteger"
            // vì nó tham chiếu đến cột "id()" (là một BigInt không dấu)
            $table->unsignedBigInteger('SpecialtyID')->nullable();

            // 2. Thiết lập liên kết (Constraint)
            $table->foreign('SpecialtyID') // Cột 'SpecialtyID' của bảng này...
                ->references('SpecialtyID') // ...tham chiếu đến cột 'SpecialtyID'...
                ->on('specialties'); // ...nằm ở trong bảng 'Specialties'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};