<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'attachment_path',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeaveType::class,
            'status' => LeaveStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isOngoing(): bool
    {
        return $this->end_date === null;
    }

    public function periodLabel(): string
    {
        if ($this->isOngoing()) {
            return 'Sejak '.$this->start_date->translatedFormat('d M Y');
        }

        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->translatedFormat('d M Y');
        }

        return $this->start_date->translatedFormat('d M Y')
            .' – '.$this->end_date->translatedFormat('d M Y');
    }

    public function totalDays(): int
    {
        $end = $this->end_date?->copy() ?? now()->startOfDay();

        if ($end->lessThan($this->start_date)) {
            return 0;
        }

        return (int) $this->start_date->diffInDays($end) + 1;
    }
}
