<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Summary Pengajaran - {{ $tutor->name }} - {{ $month->format('Y-m') }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { text-align: center; border-bottom: 2px solid #2C3E73; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 15px; color: #2C3E73; }
        .header p { margin: 2px 0 0; color: #666; }
        table.stats { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.stats td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        table.stats .label { font-size: 9px; color: #666; }
        table.stats .value { font-size: 13px; font-weight: bold; }
        table.detail { width: 100%; border-collapse: collapse; }
        table.detail th, table.detail td { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }
        table.detail th { background: #2C3E73; color: #fff; text-align: left; }
        .muted { color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SUMMARY PENGAJARAN</h1>
        <p>{{ $tutor->name }} · Bimbingan Belajar LIVO · Periode {{ $month->translatedFormat('F Y') }}</p>
    </div>

    <table class="stats">
        <tr>
            <td><div class="label">Sesi Selesai</div><div class="value">{{ $stats['done'] }}</div></td>
            <td><div class="label">Siswa Diajar</div><div class="value">{{ $stats['students'] }}</div></td>
            <td><div class="label">Dievaluasi</div><div class="value">{{ $stats['evaluated'] }}</div></td>
            <td><div class="label">Rata-rata Post Test</div><div class="value">{{ $stats['avg_post_test'] ?? '—' }}</div></td>
            <td><div class="label">Hadir</div><div class="value">{{ $stats['hadir'] }}</div></td>
            <td><div class="label">Izin</div><div class="value">{{ $stats['izin'] }}</div></td>
            <td><div class="label">Alfa</div><div class="value">{{ $stats['alfa'] }}</div></td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th width="10%">Tanggal</th>
                <th width="8%">Jam</th>
                <th width="16%">Siswa</th>
                <th width="12%">Mapel</th>
                <th width="20%">Materi</th>
                <th width="8%">Kehadiran</th>
                <th width="7%">Post Test</th>
                <th>Catatan Tutor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $s)
                <tr>
                    <td>{{ $s->class_date->format('d/m/Y') }}</td>
                    <td>{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</td>
                    <td>{{ $s->student->full_name ?? '-' }}</td>
                    <td>{{ $s->subject->subject_name ?? '-' }}</td>
                    <td>
                        @if($m = $s->evaluation?->materi_display)
                            {{ $m['pokok'] }}{{ $m['sub'] ? ' — ' . $m['sub'] : '' }}
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td>{{ ucfirst($s->evaluation->student_attendance ?? '—') }}</td>
                    <td>{{ $s->evaluation->post_test ?? '—' }}</td>
                    <td>{{ $s->evaluation->tutor_notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;" class="muted">Tidak ada sesi selesai pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
