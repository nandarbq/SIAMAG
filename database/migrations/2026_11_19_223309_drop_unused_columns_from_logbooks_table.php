<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropIndex(['date', 'status']);
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('reviewed_by');
        });

        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropColumn([
                'start_time',
                'end_time',
                'result',
                'obstacle',
                'status',
                'review_note',
                'reviewed_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('description');
            $table->time('end_time')->nullable()->after('start_time');
            $table->text('result')->nullable()->after('end_time');
            $table->text('obstacle')->nullable()->after('result');
            $table->string('status')->default('draft')->index()->after('attachment_path');
            $table->text('review_note')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('review_note');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->index(['date', 'status']);
        });
    }
};
