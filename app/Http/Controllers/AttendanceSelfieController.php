<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AttendanceSelfieController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, AttendanceService $attendanceService): JsonResponse
    {
        try {
            $validated = $request->validate([
                'photo' => ['required', 'string', 'max:2000000'],
            ], [
                'photo.required' => 'Foto selfie wajib diambil.',
                'photo.string' => 'Format foto tidak valid.',
                'photo.max' => 'Ukuran foto terlalu besar. Ambil ulang foto.',
            ]);
        } catch (ValidationException $exception) {
            $photo = $request->input('photo');

            Log::warning('Attendance selfie validation failed', [
                'user_id' => $request->user()?->id,
                'payload_length' => is_string($photo) ? strlen($photo) : null,
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        }

        $token = $attendanceService->storeSelfie($request->user(), $validated['photo']);

        return response()->json(['token' => $token]);
    }
}
