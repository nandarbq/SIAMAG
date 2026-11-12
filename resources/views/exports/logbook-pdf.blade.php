<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Logbook Magang {{ $user->name }}</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 15px; letter-spacing: .5px; color: #1e3a8a; }
        .header p { margin: 3px 0 0; font-size: 9px; color: #64748b; }
        .meta { width: 100%; margin-bottom: 12px; font-size: 9px; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .label { width: 90px; color: #64748b; }
        .meta .colon { width: 10px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1e3a8a; color: #fff; font-size: 8px; padding: 6px 4px; text-align: left; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 4px; font-size: 8px; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data .center { text-align: center; }
        .empty { text-align: center; padding: 16px; color: #94a3b8; }
        .signature { width: 100%; margin-top: 28px; font-size: 9px; }
        .signature td { width: 50%; vertical-align: top; }
        .signature .right { text-align: right; }
        .signature .space { height: 48px; }
        .footer { margin-top: 14px; font-size: 7px; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOGBOOK MAGANG</h1>
        <p>{{ $office_name }}</p>
        @if ($office_address)
            <p>{{ $office_address }}</p>
        @endif
    </div>

    <table class="meta">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td>{{ $user->name }}</td>
            <td class="label">Periode</td>
            <td class="colon">:</td>
            <td>{{ $profile->start_date->translatedFormat('d F Y') }} - {{ $profile->end_date->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td class="colon">:</td>
            <td>{{ $user->nim ?: '-' }}</td>
            <td class="label">Institusi</td>
            <td class="colon">:</td>
            <td>{{ $profile->institution ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jurusan</td>
            <td class="colon">:</td>
            <td>{{ $profile->major ?: '-' }}</td>
            <td class="label">Semester</td>
            <td class="colon">:</td>
            <td>{{ $profile->semester ?: '-' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="center" style="width: 4%;">No</th>
                <th style="width: 11%;">Tanggal</th>
                <th style="width: 17%;">Aktivitas</th>
                <th style="width: 28%;">Uraian Kegiatan</th>
                <th style="width: 20%;">Hasil</th>
                <th style="width: 13%;">Kendala</th>
                <th class="center" style="width: 7%;">Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logbooks as $index => $logbook)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $logbook->date?->translatedFormat('d M Y') ?? '-' }}</td>
                    <td>{{ $logbook->activity ?: '-' }}</td>
                    <td>{!! nl2br(e($logbook->description ?: '-')) !!}</td>
                    <td>{!! nl2br(e($logbook->result ?: '-')) !!}</td>
                    <td>{!! nl2br(e($logbook->obstacle ?: '-')) !!}</td>
                    <td class="center">
                        @if ($logbook->start_time || $logbook->end_time)
                            {{ substr((string) $logbook->start_time, 0, 5) }} - {{ substr((string) $logbook->end_time, 0, 5) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Belum ada logbook pada periode magang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td></td>
            <td class="right">
                <p>Banyuwangi, {{ $generated_at->translatedFormat('d F Y') }}</p>
                <p>Mahasiswa Magang</p>
                <div class="space"></div>
                <p><strong><u>{{ $user->name }}</u></strong></p>
                <p>NIM {{ $user->nim ?: '-' }}</p>
            </td>
        </tr>
    </table>

    <p class="footer">Dokumen ini dihasilkan otomatis oleh SIAMAG BMKG Banyuwangi.</p>
</body>
</html>
