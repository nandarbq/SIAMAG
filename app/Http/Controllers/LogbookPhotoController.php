<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogbookPhotoController extends Controller
{
    public function __invoke(Request $request, Logbook $logbook)
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isAdmin() || $user->id === $logbook->user_id),
            403,
        );

        abort_unless(
            $logbook->attachment_path && Storage::disk('local')->exists($logbook->attachment_path),
            404,
        );

        return Storage::disk('local')->response($logbook->attachment_path);
    }
}
