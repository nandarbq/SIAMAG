<?php

namespace App\Livewire\Mahasiswa;

use App\Services\LogbookProgressService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Profil Saya')]
class Profile extends Component
{
    use WithFileUploads;

    public $photo = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'phone.max' => 'Nomor telepon maksimal 30 karakter.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
        ]);

        session()->flash('success', 'Profil berhasil diperbarui.');
    }

    public function updatePhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'photo.required' => 'Pilih foto terlebih dahulu.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
            'photo.mimes' => 'Format foto harus jpg, jpeg, png, atau webp.',
        ]);

        $user = auth()->user();
        $oldPhoto = $user->photo_path;
        $path = $this->photo->store('avatars/'.$user->id, 'local');

        $user->update(['photo_path' => $path]);

        if ($oldPhoto) {
            Storage::disk('local')->delete($oldPhoto);
        }

        $this->reset('photo');
        session()->flash('success', 'Foto profil berhasil diperbarui.');
        $this->redirectRoute('mahasiswa.profile', navigate: true);
    }

    public function removePhoto(): void
    {
        $user = auth()->user();

        if (! $user->photo_path) {
            return;
        }

        Storage::disk('local')->delete($user->photo_path);
        $user->update(['photo_path' => null]);

        session()->flash('success', 'Foto profil berhasil dihapus.');
        $this->redirectRoute('mahasiswa.profile', navigate: true);
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', 'confirmed:passwordConfirmation', Password::min(8)],
        ], [
            'currentPassword.required' => 'Password saat ini wajib diisi.',
            'currentPassword.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        auth()->user()->update([
            'password' => $this->password,
        ]);

        Auth::logoutOtherDevices($this->password);

        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        session()->flash('success', 'Password berhasil diperbarui.');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.mahasiswa.profile', [
            'user' => $user,
            'profile' => $user->internProfile,
            'attendanceCount' => $user->attendances()->count(),
            'logbookProgress' => app(LogbookProgressService::class)->for($user),
        ]);
    }
}
