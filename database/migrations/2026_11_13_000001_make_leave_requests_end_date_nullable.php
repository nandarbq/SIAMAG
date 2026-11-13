<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->date('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('leave_requests')->whereNull('end_date')->update([
            'end_date' => DB::raw('start_date'),
        ]);

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->date('end_date')->nullable(false)->change();
        });
    }
};
