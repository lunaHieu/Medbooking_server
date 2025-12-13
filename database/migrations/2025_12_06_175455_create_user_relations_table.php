<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_relations', function (Blueprint $table) {
            $table->id(); // Khóa chính của bảng quan hệ này

            // 1. Cột UserID (Chủ tài khoản)
            // Phải là unsignedBigInteger để khớp với $table->id('UserID') bên bảng users
            $table->unsignedBigInteger('UserID');

            // 2. Cột RelativeUserID (Người thân)
            // Cũng phải là unsignedBigInteger
            $table->unsignedBigInteger('RelativeUserID');

            $table->string('RelationType')->nullable(); // Bố, Mẹ, Vợ, Con...
            $table->timestamps();

            // 3. Tạo khóa ngoại (Foreign Keys)
            $table->foreign('UserID')
                ->references('UserID')->on('users')
                ->onDelete('cascade');

            $table->foreign('RelativeUserID')
                ->references('UserID')->on('users')
                ->onDelete('cascade');

            // 4. Chống trùng lặp: Một người không thể thêm cùng 1 người thân 2 lần
            $table->unique(['UserID', 'RelativeUserID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_relations');
    }
};
