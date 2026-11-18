<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->dashboardRoute());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Hubungi admin.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->to($this->resolveIntendedUrl($request, Auth::user()));
    }

    private function resolveIntendedUrl(Request $request, User $user): string
    {
        $fallback = route($user->dashboardRoute());
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        $parts = parse_url($intended);

        if ($parts === false) {
            return $fallback;
        }

        $host = $parts['host'] ?? null;

        if ($host !== null && strcasecmp($host, $request->getHost()) !== 0) {
            return $fallback;
        }

        $path = '/'.ltrim((string) ($parts['path'] ?? '/'), '/');

        if (str_contains($path, '..')) {
            return $fallback;
        }

        $prefix = $user->isAdmin() ? '/admin' : '/mahasiswa';

        if ($path === '/' || $path === $prefix || str_starts_with($path, $prefix.'/')) {
            return $intended;
        }

        return $fallback;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
