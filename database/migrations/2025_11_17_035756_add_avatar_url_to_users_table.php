<?php
// Tên file: ..._add_avatar_url_to_users_table.php (Bản Sạch)

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
        // 1. Sửa '=users' thành 'users'
        Schema::table('users', function (Blueprint $table) {
            
            // 2. Thêm cột 'avatar_url'
            $table->string('avatar_url', 500)->nullable()->after('Status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Sửa '=users' thành 'users'
        Schema::table('users', function (Blueprint $table) {
            
            // 2. Thêm logic "hoàn tác" (xóa cột)
            $table->dropColumn('avatar_url');
        });
    }
};