<?php

namespace App\Models;

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
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
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

    public function isEditable(): bool
    {
        $profile = $this->user?->internProfile;

        if (! $profile) {
            return true;
        }

        return today()->betweenIncluded($profile->start_date, $profile->end_date);
    }
}
