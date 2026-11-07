<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\LogbookStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\InternProfile;
use App\Models\LeaveRequest;
use App\Models\Logbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@bmkg.go.id')->first();
        $demo = User::query()->where('email', 'mahasiswa@bmkg.go.id')->first();

        if (! $admin || ! $demo) {
            return;
        }

        $others = User::factory()
            ->count(5)
            ->mahasiswa()
            ->create();

        $students = collect([$demo])->merge($others);

        foreach ($students as $student) {
            InternProfile::firstOrCreate(
                ['user_id' => $student->id],
                [
                    'institution' => fake()->randomElement([
                        'Universitas Jember',
                        'Politeknik Negeri Banyuwangi',
                        'Universitas Brawijaya',
                        'UIN Kiai Haji Achmad Siddiq Jember',
                    ]),
                    'major' => fake()->randomElement([
                        'Teknik Informatika',
                        'Sistem Informasi',
                        'Ilmu Komputer',
                        'Teknologi Informasi',
                    ]),
                    'semester' => (string) fake()->numberBetween(4, 8),
                    'start_date' => now()->subMonths(2)->startOfMonth(),
                    'end_date' => now()->addMonths(2)->endOfMonth(),
                    'field_supervisor' => fake()->name(),
                    'supervisor_phone' => fake()->numerify('08##########'),
                ]
            );
        }

        $holidayDates = Holiday::query()->pluck('date')->map(fn ($date) => $date->toDateString())->all();

        for ($i = 20; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            if ($date->isWeekend() || in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            foreach ($students as $student) {
                $roll = fake()->numberBetween(1, 100);

                if ($roll <= 6) {
                    $status = AttendanceStatus::Izin;
                } elseif ($roll <= 10) {
                    $status = AttendanceStatus::Sakit;
                } elseif ($roll <= 35) {
                    $status = AttendanceStatus::Terlambat;
                } else {
                    $status = AttendanceStatus::Hadir;
                }

                $isPresent = in_array($status, [AttendanceStatus::Hadir, AttendanceStatus::Terlambat], true);
                $late = $status === AttendanceStatus::Terlambat;
                $checkIn = $late
                    ? $date->copy()->setTime(8, fake()->numberBetween(5, 40))
                    : $date->copy()->setTime(7, fake()->numberBetween(20, 44));
                $checkOut = $date->copy()->setTime(16, fake()->numberBetween(0, 35));

                Attendance::updateOrCreate(
                    ['user_id' => $student->id, 'date' => $date->toDateString()],
                    [
                        'status' => $status,
                        'check_in_at' => $isPresent ? $checkIn : null,
                        'check_out_at' => $isPresent ? $checkOut : null,
                        'late_minutes' => $late ? fake()->numberBetween(5, 45) : 0,
                        'check_in_lat' => $isPresent ? -8.2192000 : null,
                        'check_in_lng' => $isPresent ? 114.3692000 : null,
                        'check_out_lat' => $isPresent ? -8.2192000 : null,
                        'check_out_lng' => $isPresent ? 114.3692000 : null,
                        'check_in_distance' => $isPresent ? fake()->numberBetween(5, 90) : null,
                        'check_out_distance' => $isPresent ? fake()->numberBetween(5, 90) : null,
                        'check_in_accuracy' => $isPresent ? fake()->numberBetween(5, 35) : null,
                        'check_out_accuracy' => $isPresent ? fake()->numberBetween(5, 35) : null,
                        'check_in_ip' => $isPresent ? '127.0.0.1' : null,
                        'check_out_ip' => $isPresent ? '127.0.0.1' : null,
                    ]
                );

                if ($isPresent && $student->is($demo)) {
                    $logbookStatus = match (true) {
                        $i <= 2 => LogbookStatus::Draft,
                        $i <= 5 => LogbookStatus::Submitted,
                        $i <= 8 => LogbookStatus::Approved,
                        default => LogbookStatus::Draft,
                    };

                    Logbook::updateOrCreate(
                        ['user_id' => $student->id, 'date' => $date->toDateString()],
                        [
                            'activity' => fake()->randomElement([
                                'Membantu pengolahan data cuaca harian',
                                'Menginput data observasi ke sistem',
                                'Mempelajari alur kerja Stasiun Meteorologi',
                                'Menyusun dokumentasi kegiatan magang',
                                'Membantu pemeriksaan peralatan observasi',
                            ]),
                            'description' => fake()->paragraph(4),
                            'start_time' => '08:00',
                            'end_time' => '15:30',
                            'result' => fake()->sentence(12),
                            'obstacle' => fake()->boolean(30) ? fake()->sentence(8) : null,
                            'status' => $logbookStatus,
                            'review_note' => $logbookStatus === LogbookStatus::Approved ? 'Logbook sudah baik, lanjutkan.' : null,
                            'reviewed_by' => $logbookStatus === LogbookStatus::Approved ? $admin->id : null,
                            'reviewed_at' => $logbookStatus === LogbookStatus::Approved ? now() : null,
                        ]
                    );
                }
            }
        }

        LeaveRequest::firstOrCreate(
            [
                'user_id' => $demo->id,
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
            ],
            [
                'type' => LeaveType::Izin,
                'reason' => 'Mengikuti kegiatan seminar kampus.',
                'status' => LeaveStatus::Pending,
            ]
        );
    }
}
