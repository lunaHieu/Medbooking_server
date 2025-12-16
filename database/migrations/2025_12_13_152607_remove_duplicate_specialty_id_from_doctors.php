<?php

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
    Schema::table('doctors', function (Blueprint $table) {
        // Đơn giản: chỉ drop column, DB sẽ tự xóa foreign key
        if (Schema::hasColumn('doctors', 'specialty_id')) {
            $table->dropColumn('specialty_id');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Khi rollback, tạo lại cột (không cần foreign key)
            if (!Schema::hasColumn('doctors', 'specialty_id')) {
                $table->foreignId('specialty_id')
                      ->nullable()
                      ->constrained('specialties')
                      ->onDelete('set null');
            }
        });
    }
};