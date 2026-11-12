<?php

namespace App\Models;

use App\Enums\LogbookStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'date',
        'activity',
        'description',
        'start_time',
        'end_time',
        'result',
        'obstacle',
        'attachment_path',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => LogbookStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        if ($end <= $start) {
            return null;
        }

        $minutes = (int) round(($end - $start) / 60);

        return sprintf('%d jam %02d menit', intdiv($minutes, 60), $minutes % 60);
    }

    public function isEditable(): bool
    {
        $profile = $this->user?->internProfile;

        if (! $profile) {
            return true;
        }

        return today()->betweenIncluded($profile->start_date, $profile->end_date);
    }
}
