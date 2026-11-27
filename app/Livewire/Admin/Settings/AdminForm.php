<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Form Administrator')]
class AdminForm extends Component
{
    public ?User $admin = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_active = true;

    public function mount(?User $admin = null): void
    {
        abort_unless($admin === null || $admin->role === UserRole::Admin, 404);

        $this->admin = $admin;
        $this->name = $admin?->name ?? '';
        $this->email = $admin?->email ?? '';
        $this->is_active = $admin?->is_active ?? true;
    }

    public function save(): ?RedirectResponse
    {
        $validated = $this->validate($this->validationRules(), $this->validationMessages());

        if ($this->admin?->id === auth()->id() && ! $validated['is_active']) {
            $this->addError('is_active', 'Tidak dapat mengubah status akun sendiri.');

            return null;
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => (bool) $validated['is_active'],
        ];

        if ($this->admin) {
            if (filled($validated['password'] ?? null)) {
                $data['password'] = $validated['password'];
            }

            $this->admin->update($data);
            $message = 'Akun administrator berhasil diperbarui.';
        } else {
            User::query()->create(array_merge($data, [
                'password' => $validated['password'],
                'role' => UserRole::Admin,
                'nim' => null,
            ]));
            $message = 'Akun administrator berhasil dibuat.';
        }

        session()->flash('success', $message);

        return redirect()->route('admin.settings');
    }

    public function render()
    {
        return view('livewire.admin.settings.admin-form');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function validationRules(): array
    {
        $emailRule = Rule::unique('users', 'email');

        if ($this->admin) {
            $emailRule->ignore($this->admin->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'password' => [$this->admin ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'name.required' => 'Nama administrator wajib diisi.',
            'email.required' => 'Email administrator wajib diisi.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
