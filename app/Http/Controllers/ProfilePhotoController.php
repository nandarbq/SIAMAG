<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        $requester = $request->user();

        abort_unless(
            $requester && ($requester->isAdmin() || $requester->id === $user->id),
            403,
        );

        abort_unless(
            $user->photo_path && Storage::disk('local')->exists($user->photo_path),
            404,
        );

        return Storage::disk('local')->response($user->photo_path);
    }
}
