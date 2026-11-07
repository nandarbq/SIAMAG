<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveAttachmentController extends Controller
{
    public function __invoke(Request $request, LeaveRequest $leave)
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isAdmin() || $user->id === $leave->user_id),
            403,
        );

        abort_unless(
            $leave->attachment_path && Storage::disk('local')->exists($leave->attachment_path),
            404,
        );

        return Storage::disk('local')->response($leave->attachment_path);
    }
}
