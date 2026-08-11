<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Summary Pengajaran - {{ $tutor->name }} - {{ $month->format('Y-m') }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; }
        .header { text-align: center; border-bottom: 2px solid #2C3E73; padding-bottom: 6px; margin-bottom: 8px; }
        .header h1 { margin: 0; font-size: 15px; color: #2C3E73; }
        .header p { margin: 2px 0 0; color: #666; font-size: 9px; }

        table.stats { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.stats td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        table.stats .label { font-size: 8px; color: #666; }
        table.stats .value { font-size: 12px; font-weight: bold; }

        table.layout { width: 100%; border-collapse: collapse; }
        td.col-left { width: 58%; padding-right: 8px; vertical-align: top; }
        td.col-right { width: 42%; padding-left: 8px; border-left: 1px solid #ddd; vertical-align: top; }

        h3.section-title { font-size: 10px; color: #2C3E73; margin: 0 0 4px; border-bottom: 1px solid #2C3E73; padding-bottom: 2px; }
        h3.chart-title { font-size: 10px; color: #2C3E73; margin: 0 0 4px; }

        table.detail { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.detail th, table.detail td { border: 1px solid #ccc; padding: 3px 4px; vertical-align: top; }
        table.detail th { background: #2C3E73; color: #fff; text-align: left; }

        table.materi { width: 100%; border-collapse: collapse; }
        table.materi th, table.materi td { border: 1px solid #ccc; padding: 3px 4px; vertical-align: top; }
        table.materi th { background: #22A699; color: #fff; text-align: left; }

        .chart-block { margin-bottom: 10px; }

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

    <table class="layout">
        <tr>
            <td class="col-left">
                <h3 class="section-title">Rekap Pembelajaran</h3>
                <table class="detail">
                    <thead>
                        <tr>
                            <th width="12%">Tanggal</th>
                            <th width="20%">Siswa</th>
                            <th width="16%">Mapel</th>
                            <th width="12%">Kehadiran</th>
                            <th width="10%">Post Test</th>
                            <th>Catatan Tutor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $s)
                            <tr>
                                <td>{{ $s->class_date->format('d/m/y') }}<br><span class="muted">{{ substr($s->start_time, 0, 5) }}</span></td>
                                <td>{{ $s->student->full_name ?? '-' }}</td>
                                <td>{{ $s->subject->subject_name ?? '-' }}</td>
                                <td>{{ ucfirst($s->evaluation->student_attendance ?? '—') }}</td>
                                <td>{{ $s->evaluation->post_test ?? '—' }}</td>
                                <td>{{ $s->evaluation->tutor_notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;" class="muted">Tidak ada sesi selesai pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <h3 class="section-title" style="margin-top:10px">Materi Pembahasan</h3>
                <table class="materi">
                    <thead>
                        <tr>
                            <th width="12%">Tanggal</th>
                            <th width="20%">Siswa</th>
                            <th width="16%">Mapel</th>
                            <th>Materi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $s)
                            <tr>
                                <td>{{ $s->class_date->format('d/m/y') }}</td>
                                <td>{{ $s->student->full_name ?? '-' }}</td>
                                <td>{{ $s->subject->subject_name ?? '-' }}</td>
                                <td>
                                    @if($m = $s->evaluation?->materi_display)
                                        {{ $m['pokok'] }}{{ $m['sub'] ? ' — ' . $m['sub'] : '' }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;" class="muted">Tidak ada materi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td class="col-right">
                <div class="chart-block">
                    <h3 class="chart-title">Grafik Sesi per Bulan</h3>
                    <img src="{{ $chartSesiPerBulan }}" alt="Grafik Sesi per Bulan" width="320" height="153">
                </div>
                <div class="chart-block">
                    <h3 class="chart-title">Grafik Kemampuan</h3>
                    <img src="{{ $chartKemampuan }}" alt="Grafik Kemampuan" width="320" height="153">
                </div>
                <div class="chart-block">
                    <h3 class="chart-title">Grafik Mata Pelajaran</h3>
                    <img src="{{ $chartMapel }}" alt="Grafik Mata Pelajaran" width="320" height="153">
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
