<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('mahasiswa')->after('password')->index();
            $table->string('nim', 30)->nullable()->unique()->after('role');
            $table->string('phone', 30)->nullable()->after('nim');
            $table->string('photo_path')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nim']);
            $table->dropColumn(['role', 'nim', 'phone', 'photo_path', 'is_active']);
        });
    }
};
