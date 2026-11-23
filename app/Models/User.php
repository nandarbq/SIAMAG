<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nim',
        'phone',
        'photo_path',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function internProfile()
    {
        return $this->hasOne(InternProfile::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function logbooks()
    {
        return $this->hasMany(Logbook::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === UserRole::Mahasiswa;
    }

    #[Scope]
    protected function currentInterns(Builder $query): Builder
    {
        return $query
            ->where('role', UserRole::Mahasiswa)
            ->where('is_active', true)
            ->whereDoesntHave('internProfile', fn (Builder $query): Builder => $query
                ->whereDate('end_date', '<', today()->toDateString()));
    }

    #[Scope]
    protected function completedInterns(Builder $query): Builder
    {
        return $query
            ->where('role', UserRole::Mahasiswa)
            ->whereHas('internProfile', fn (Builder $query): Builder => $query
                ->whereDate('end_date', '<', today()->toDateString()));
    }

    public function maskedNim(): ?string
    {
        if (! $this->nim) {
            return null;
        }

        $nim = (string) $this->nim;
        $length = mb_strlen($nim);

        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return mb_substr($nim, 0, 2).str_repeat('•', $length - 4).mb_substr($nim, -2);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(collect($parts)->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    }

    public function avatarUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return route('user.photo', [
            'user' => $this,
            'v' => md5($this->photo_path),
        ]);
    }

    public function dashboardRoute(): string
    {
        return $this->isAdmin() ? 'admin.dashboard' : 'mahasiswa.dashboard';
    }
}
