<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendancePhotoController extends Controller
{
    public function __invoke(Request $request, Attendance $attendance, string $type)
    {
        abort_unless(in_array($type, ['in', 'out'], true), 404);

        $user = $request->user();

        abort_unless(
            $user && ($user->isAdmin() || $user->id === $attendance->user_id),
            403,
        );

        $path = $type === 'in'
            ? $attendance->check_in_photo
            : $attendance->check_out_photo;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
