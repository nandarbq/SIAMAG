<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_profiles', function (Blueprint $table) {
            $table->foreignId('intern_group_id')
                ->nullable()
                ->after('user_id')
                ->constrained('intern_groups')
                ->nullOnDelete();
        });

        $this->backfillExistingProfiles();
    }

    public function down(): void
    {
        Schema::table('intern_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('intern_group_id');
        });
    }

    private function backfillExistingProfiles(): void
    {
        $records = DB::table('intern_profiles')
            ->select('institution', 'field_supervisor', 'supervisor_phone', 'start_date', 'end_date')
            ->distinct()
            ->orderBy('institution')
            ->orderBy('start_date')
            ->get();

        $year = now()->year;

        foreach ($records as $index => $record) {
            $groupId = DB::table('intern_groups')->insertGetId([
                'code' => sprintf('MAG-%d-%03d', $year, $index + 1),
                'name' => sprintf('Kelompok %03d - %s', $index + 1, $record->institution),
                'institution' => $record->institution,
                'field_supervisor' => $record->field_supervisor,
                'supervisor_phone' => $record->supervisor_phone,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $query = DB::table('intern_profiles')
                ->where('institution', $record->institution)
                ->where('start_date', $record->start_date)
                ->where('end_date', $record->end_date);

            foreach (['field_supervisor', 'supervisor_phone'] as $column) {
                $query->when(
                    $record->{$column} === null,
                    fn ($builder) => $builder->whereNull($column),
                    fn ($builder) => $builder->where($column, $record->{$column}),
                );
            }

            $query->update(['intern_group_id' => $groupId]);
        }
    }
};
