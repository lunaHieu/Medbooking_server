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
    Schema::table('feedbacks', function (Blueprint $table) {
        $table->text('Reply')->nullable()->after('Comment'); // Cột Trả lời
        $table->string('Status', 50)->default('Visible')->after('Rating'); // Trạng thái: Visible/Hidden
    });
}

public function down(): void
{
    Schema::table('feedbacks', function (Blueprint $table) {
        $table->dropColumn(['Reply', 'Status']);
    });
}
};
