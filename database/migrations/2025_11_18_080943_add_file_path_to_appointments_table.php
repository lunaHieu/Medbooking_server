<?php
// ... (các dòng 'use' ở trên) ...
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Thêm cột file_path (cho phép null)
            // Đặt nó sau cột 'CancellationReason'
            $table->string('file_path', 500)->nullable()->after('CancellationReason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Logic "hoàn tác" (xóa cột)
            $table->dropColumn('file_path');
        });
    }
};