<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Hadir = 'hadir';
    case Terlambat = 'terlambat';
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Alpha = 'alpha';
    case Libur = 'libur';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Terlambat => 'Terlambat',
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::Alpha => 'Alpha',
            self::Libur => 'Libur',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Hadir => 'emerald',
            self::Terlambat => 'amber',
            self::Izin => 'sky',
            self::Sakit => 'violet',
            self::Alpha => 'rose',
            self::Libur => 'slate',
        };
    }
}
