<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceSelfieController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, AttendanceService $attendanceService): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'string', 'max:2000000'],
        ], [
            'photo.required' => 'Foto selfie wajib diambil.',
            'photo.string' => 'Format foto tidak valid.',
            'photo.max' => 'Ukuran foto terlalu besar. Ambil ulang foto.',
        ]);

        $token = $attendanceService->storeSelfie($request->user(), $validated['photo']);

        return response()->json(['token' => $token]);
    }
}
