<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi {{ $period_label }}</title>
    <style>
        @page { margin: 26px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 15px; letter-spacing: .5px; color: #1e3a8a; }
        .header p { margin: 3px 0 0; font-size: 9px; color: #64748b; }
        .meta { width: 100%; margin-bottom: 10px; font-size: 9px; }
        .meta td { padding: 2px 0; }
        .meta .right { text-align: right; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1e3a8a; color: #fff; font-size: 8px; padding: 6px 4px; text-align: left; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 4px; font-size: 8px; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data .center { text-align: center; }
        table.data .total td { background: #eff6ff; font-weight: bold; border-top: 1px solid #bfdbfe; }
        .signature { width: 100%; margin-top: 28px; font-size: 9px; }
        .signature td { width: 50%; vertical-align: top; }
        .signature .right { text-align: right; }
        .signature .space { height: 48px; }
        .footer { margin-top: 14px; font-size: 7px; color: #94a3b8; text-align: right; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 8px; background: #eff6ff; color: #1d4ed8; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAP ABSENSI &amp; LOGBOOK MAGANG</h1>
        <p>{{ $office_name }} · {{ $office_address }}</p>
        <p>Periode {{ $period_label }}</p>
    </div>

    <table class="meta">
        <tr>
            <td>Total mahasiswa: <strong>{{ $summary['students'] }}</strong></td>
            <td class="right">Dicetak: {{ $generated_at->translatedFormat('d F Y H:i') }} WIB</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 19%;">Nama</th>
                <th style="width: 9%;">NIM</th>
                <th style="width: 20%;">Institusi</th>
                <th class="center" style="width: 6%;">Hadir</th>
                <th class="center" style="width: 7%;">Terlambat</th>
                <th class="center" style="width: 5%;">Izin</th>
                <th class="center" style="width: 6%;">Sakit</th>
                <th class="center" style="width: 6%;">Alpha</th>
                <th class="center" style="width: 9%;">Logbook</th>
                <th class="center" style="width: 10%;">Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td>{{ $row['nim'] }}</td>
                    <td>{{ $row['institution'] }}</td>
                    <td class="center">{{ $row['hadir'] }}</td>
                    <td class="center">{{ $row['terlambat'] }}</td>
                    <td class="center">{{ $row['izin'] }}</td>
                    <td class="center">{{ $row['sakit'] }}</td>
                    <td class="center">{{ $row['alpha'] }}</td>
                    <td class="center">{{ $row['logbook_approved'] }}/{{ $row['logbook_total'] }}</td>
                    <td class="center"><span class="badge">{{ $row['percentage'] }}%</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center; padding: 16px; color:#94a3b8;">Belum ada data pada periode ini.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td colspan="4">TOTAL</td>
                <td class="center">{{ $summary['hadir'] }}</td>
                <td class="center">{{ $summary['terlambat'] }}</td>
                <td class="center">{{ $summary['izin'] }}</td>
                <td class="center">{{ $summary['sakit'] }}</td>
                <td class="center">{{ $summary['alpha'] }}</td>
                <td class="center">{{ $summary['logbook_approved'] }}</td>
                <td class="center">-</td>
            </tr>
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td>
                <p>Mengetahui,</p>
                <p>Kepala Stasiun Meteorologi</p>
                <div class="space"></div>
                <p>............................................</p>
            </td>
            <td class="right">
                <p>Banyuwangi, {{ $generated_at->translatedFormat('d F Y') }}</p>
                <p>Administrator SIAMAG</p>
                <div class="space"></div>
                <p>{{ auth()->user()->name ?? 'Administrator' }}</p>
            </td>
        </tr>
    </table>

    <p class="footer">Dokumen ini dihasilkan otomatis oleh SIAMAG BMKG Banyuwangi.</p>
</body>
</html>
