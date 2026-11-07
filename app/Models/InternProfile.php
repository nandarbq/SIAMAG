<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'institution',
        'major',
        'semester',
        'start_date',
        'end_date',
        'field_supervisor',
        'supervisor_phone',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function progressPercent(): int
    {
        $start = $this->start_date;
        $end = $this->end_date;

        if (! $start || ! $end || $end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $current = now()->startOfDay();

        if ($current->lessThan($start)) {
            $current = $start->copy();
        } elseif ($current->greaterThan($end)) {
            $current = $end->copy();
        }

        $total = $start->diffInDays($end);
        $elapsed = $start->diffInDays($current);

        return (int) round(($elapsed / max($total, 1)) * 100);
    }

    public function remainingDays(): int
    {
        if (! $this->end_date) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false));
    }
}
