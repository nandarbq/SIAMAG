<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'check_in_at',
        'check_out_at',
        'status',
        'late_minutes',
        'check_in_photo',
        'check_out_photo',
        'check_in_lat',
        'check_in_lng',
        'check_out_lat',
        'check_out_lng',
        'check_in_distance',
        'check_out_distance',
        'check_in_accuracy',
        'check_out_accuracy',
        'check_in_ip',
        'check_out_ip',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'status' => AttendanceStatus::class,
            'late_minutes' => 'integer',
            'check_in_lat' => 'float',
            'check_in_lng' => 'float',
            'check_out_lat' => 'float',
            'check_out_lng' => 'float',
            'check_in_distance' => 'integer',
            'check_out_distance' => 'integer',
            'check_in_accuracy' => 'integer',
            'check_out_accuracy' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logbook()
    {
        return $this->hasOne(Logbook::class);
    }

    public function getCheckInTimeAttribute(): ?string
    {
        return $this->check_in_at?->format('H:i');
    }

    public function getCheckOutTimeAttribute(): ?string
    {
        return $this->check_out_at?->format('H:i');
    }

    public function getWorkDurationAttribute(): ?string
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return null;
        }

        $minutes = (int) $this->check_in_at->diffInMinutes($this->check_out_at);

        return sprintf('%d jam %02d menit', intdiv($minutes, 60), $minutes % 60);
    }

    public function isLate(): bool
    {
        return $this->status === AttendanceStatus::Terlambat;
    }
}
