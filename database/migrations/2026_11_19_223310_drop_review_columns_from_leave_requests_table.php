<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['review_note', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('review_note');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
        });
    }
};
