<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class StudentFileCleaner
{
    public function deleteFor(User $user): void
    {
        $user->loadMissing(['attendances', 'leaveRequests', 'logbooks']);

        $paths = $user->attendances
            ->flatMap(fn ($attendance) => [$attendance->check_in_photo, $attendance->check_out_photo])
            ->merge($user->leaveRequests->pluck('attachment_path'))
            ->merge($user->logbooks->pluck('attachment_path'))
            ->filter()
            ->unique();

        $paths->each(fn (string $path) => Storage::disk('local')->delete($path));
    }
}
