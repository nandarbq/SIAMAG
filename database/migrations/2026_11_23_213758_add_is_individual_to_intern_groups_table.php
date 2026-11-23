<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_groups', function (Blueprint $table) {
            $table->boolean('is_individual')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('intern_groups', function (Blueprint $table) {
            $table->dropColumn('is_individual');
        });
    }
};
