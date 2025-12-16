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
        // [FIX LỖI 1060: Duplicate column name 'Type']
        Schema::table('appointments', function (Blueprint $table) {
            // CHỈ THÊM CỘT NẾU NÓ CHƯA TỒN TẠI
            if (!Schema::hasColumn('appointments', 'Type')) { 
                $table->string('Type')->default('New')->after('Status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // [FIX BỔ SUNG] Đảm bảo logic rollback chuẩn
        Schema::table('appointments', function (Blueprint $table) {
            // CHỈ XÓA CỘT NẾU NÓ ĐANG TỒN TẠI
            if (Schema::hasColumn('appointments', 'Type')) { 
                $table->dropColumn('Type');
            }
        });
    }
};