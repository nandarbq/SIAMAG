<?php

namespace App\Livewire\Admin\Students;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Form Mahasiswa')]
class Form extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $nim = '';

    public string $phone = '';

    public bool $is_active = true;

    public string $institution = '';

    public string $major = '';

    public string $semester = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $field_supervisor = '';

    public string $supervisor_phone = '';

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            abort_unless($user->role === UserRole::Mahasiswa, 404);

            $this->user = $user;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->nim = $user->nim ?? '';
            $this->phone = $user->phone ?? '';
            $this->is_active = (bool) $user->is_active;

            $profile = $user->internProfile;

            if ($profile) {
                $this->institution = $profile->institution;
                $this->major = $profile->major;
                $this->semester = (string) $profile->semester;
                $this->start_date = $profile->start_date?->format('Y-m-d') ?? '';
                $this->end_date = $profile->end_date?->format('Y-m-d') ?? '';
                $this->field_supervisor = $profile->field_supervisor ?? '';
                $this->supervisor_phone = $profile->supervisor_phone ?? '';
            }
        } else {
            $this->start_date = now()->toDateString();
            $this->end_date = now()->addMonths(4)->toDateString();
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'min:8'],
            'nim' => ['required', 'string', 'max:30', Rule::unique('users', 'nim')->ignore($this->user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'institution' => ['required', 'string', 'max:255'],
            'major' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'field_supervisor' => ['nullable', 'string', 'max:255'],
            'supervisor_phone' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'password.required' => 'Password wajib diisi untuk akun baru.',
            'password.min' => 'Password minimal 8 karakter.',
            'institution.required' => 'Institusi wajib diisi.',
            'major.required' => 'Jurusan wajib diisi.',
            'semester.required' => 'Semester wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nim' => $validated['nim'],
            'phone' => $validated['phone'] ?: null,
            'is_active' => $this->is_active,
            'role' => UserRole::Mahasiswa,
        ];

        if (! empty($validated['password'])) {
            $userData['password'] = $validated['password'];
        }

        if ($this->user) {
            $this->user->update($userData);
            $user = $this->user;
        } else {
            $user = User::query()->create($userData);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->internProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'institution' => $validated['institution'],
                'major' => $validated['major'],
                'semester' => $validated['semester'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'field_supervisor' => $validated['field_supervisor'] ?: null,
                'supervisor_phone' => $validated['supervisor_phone'] ?: null,
            ]
        );

        session()->flash('success', $this->user
            ? 'Data mahasiswa berhasil diperbarui.'
            : 'Akun mahasiswa berhasil ditambahkan.');

        return redirect()->route('admin.students');
    }

    public function render()
    {
        return view('livewire.admin.students.form');
    }
}
