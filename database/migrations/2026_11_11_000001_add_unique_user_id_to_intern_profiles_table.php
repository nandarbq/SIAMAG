<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('intern_profiles')
            ->select('user_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $profile): void {
                DB::table('intern_profiles')
                    ->where('user_id', $profile->user_id)
                    ->where('id', '!=', $profile->keep_id)
                    ->delete();
            });

        Schema::table('intern_profiles', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('intern_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }
};
