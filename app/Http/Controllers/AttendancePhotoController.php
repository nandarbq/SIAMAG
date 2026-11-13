<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;

class AttendancePhotoController extends Controller
{
    public function __invoke(Attendance $attendance, string $type)
    {
        abort_unless(in_array($type, ['in', 'out'], true), 404);

        $path = $type === 'in'
            ? $attendance->check_in_photo
            : $attendance->check_out_photo;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
