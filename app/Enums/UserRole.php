<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Mahasiswa = 'mahasiswa';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Mahasiswa => 'Mahasiswa Magang',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'sky',
            self::Mahasiswa => 'emerald',
        };
    }
}
