<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Component;

class AdminAccounts extends Component
{
    public string $notice = '';

    public string $noticeType = 'success';

    public function toggleActive(int $userId): void
    {
        $admin = $this->findAdmin($userId);

        if ($admin->id === auth()->id()) {
            $this->addError('admin_'.$admin->id, 'Tidak dapat mengubah status akun sendiri.');

            return;
        }

        $admin->update(['is_active' => ! $admin->is_active]);

        $this->notice = $admin->is_active
            ? 'Akun administrator diaktifkan.'
            : 'Akun administrator dinonaktifkan.';
        $this->noticeType = 'success';
    }

    public function render()
    {
        return view('livewire.admin.settings.admin-accounts', [
            'admins' => User::query()
                ->where('role', UserRole::Admin)
                ->orderBy('name')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    private function findAdmin(int $userId): User
    {
        return User::query()
            ->whereKey($userId)
            ->where('role', UserRole::Admin)
            ->firstOrFail();
    }
}
