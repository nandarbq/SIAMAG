<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('leave_requests')
            ->where('status', 'pending')
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
    }
};
