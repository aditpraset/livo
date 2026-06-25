<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Hasil Belajar - {{ $student->full_name }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #222; margin: 0; }
        .header-top { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-top td { vertical-align: middle; }
        .title { text-align: center; font-size: 20px; font-weight: bold; letter-spacing: .5px; }
        .subtitle { text-align: center; font-size: 10px; color: #444; }
        .logo { height: 46px; }
        table.info { width: 100%; border-collapse: collapse; margin: 6px 0 12px; font-size: 9.5px; }
        table.info td { padding: 2px 4px; vertical-align: top; }
        .info .lbl { font-weight: bold; width: 58px; }
        .info .sep { width: 6px; }
        .periode-box { border: 1px solid #999; text-align: center; font-weight: bold; padding: 6px; font-size: 10px; width: 130px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 4px; color: #fff; font-weight: bold; font-size: 9.5px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data th, table.data td { border: 1px solid #777; padding: 5px 4px; text-align: center; }
        table.data thead th { background: #cfd8ea; font-size: 9px; font-weight: bold; }
        table.data tbody td { font-size: 9.5px; }
        table.data .avg-row td { background: #dbe6f5; font-weight: bold; }
        table.data .name-col { text-align: left; padding-left: 8px; }

        .two-col { width: 100%; border-collapse: collapse; }
        .two-col > td { vertical-align: top; width: 50%; padding: 0 6px; }
        .section-title { font-weight: bold; font-size: 10px; margin: 4px 0 6px; }

        table.materi { width: 100%; border-collapse: collapse; }
        table.materi th, table.materi td { border: 1px solid #777; padding: 4px 5px; font-size: 9px; }
        table.materi thead th { background: #cfd8ea; text-align: center; }
        table.materi .no { width: 28px; text-align: center; }
        table.materi .val { width: 80px; text-align: center; }
        table.materi .lbl-r { text-align: right; font-weight: bold; border: none; }

        .catatan-box { border: 1px solid #777; height: 70px; }
        .ttd { width: 100%; margin-top: 6px; }
        .ttd td { vertical-align: top; font-size: 9.5px; }

        /* Grafik batang sesi per bulan */
        table.bar-chart { width: 100%; border-collapse: collapse; }
        table.bar-chart td { padding: 3px 4px; font-size: 9px; vertical-align: middle; }
        .bc-label { width: 64px; font-weight: bold; white-space: nowrap; }
        .bc-track { background: #eef2f9; border: 1px solid #d4ddec; }
        .bc-bar { background: #2C3E73; height: 11px; line-height: 11px; font-size: 0; }
        .bc-val { width: 50px; font-weight: bold; text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
@php
    $predColor = $predikat === 'Amat Baik' ? '#16a34a' : ($predikat === 'Baik' ? '#2563eb' : ($predikat === 'Cukup' ? '#d97706' : '#dc2626'));
    $fmt = fn($v) => $v === null ? '-' : number_format($v, 0);
    $colspanInfo = 3 + count($programs) + 3;
@endphp

{{-- ── Header ── --}}
<table class="header-top">
    <tr>
        <td style="width:90px;">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td>
        <td>
            <div class="title">LAPORAN HASIL BELAJAR</div>
            <div class="subtitle">Lembaga Bimbingan Belajar - Learning Innovation (LIVO)</div>
        </td>
        <td style="width:90px;"></td>
    </tr>
</table>

<table class="info">
    <tr>
        <td rowspan="2" style="width:140px;"><div class="periode-box">Periode<br>{{ $periode }}</div></td>
        <td class="lbl">NIS</td><td class="sep">:</td>
        <td>{{ $student->nis ?? '-' }}{{ $student->nickname ? ' - ' . $student->nickname : '' }}</td>
        <td class="lbl">Kelas</td><td class="sep">:</td>
        <td>{{ $student->grade ?? '-' }}</td>
        <td class="lbl">Program</td><td class="sep">:</td>
        <td>{{ $student->package ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Nama</td><td class="sep">:</td>
        <td>{{ $student->full_name }}</td>
        <td class="lbl">Sekolah</td><td class="sep">:</td>
        <td>{{ $student->school_origin ?? '-' }}</td>
        <td class="lbl">Predikat</td><td class="sep">:</td>
        <td><span class="badge" style="background:{{ $predColor }};">{{ $predikat }}</span></td>
    </tr>
</table>

{{-- ── Tabel utama: rata-rata per bulan ── --}}
<table class="data">
    <thead>
        <tr>
            <th style="width:28px;">No.</th>
            <th class="name-col">Nama Bulan</th>
            <th>Akumulasi Sesi</th>
            @foreach($programs as $prog)
                <th>Nilai Rata-rata ({{ $prog }})</th>
            @endforeach
            <th>Kemampuan Analisa</th>
            <th>Kemampuan Hafalan</th>
            <th>Kepercayaan Diri</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $r)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="name-col">{{ $r['label'] }}</td>
            <td>{{ $r['sesi'] }} Sesi</td>
            @foreach($programs as $prog)
                <td>{{ $fmt($r['subjects'][$prog] ?? null) }}</td>
            @endforeach
            <td>{{ $fmt($r['analisa']) }}</td>
            <td>{{ $fmt($r['hafalan']) }}</td>
            <td>{{ $fmt($r['kepercayaan']) }}</td>
        </tr>
        @empty
        <tr><td colspan="{{ $colspanInfo }}" style="padding:14px; color:#888;">Belum ada data evaluasi pada periode ini.</td></tr>
        @endforelse

        @if(count($rows))
        <tr class="avg-row">
            <td colspan="2">Nilai Rata-Rata</td>
            <td>{{ $footer['sesi'] }} Sesi/Bulan</td>
            @foreach($programs as $prog)
                <td>{{ $fmt($footer['subjects'][$prog] ?? null) }}</td>
            @endforeach
            <td>{{ $fmt($footer['analisa']) }}</td>
            <td>{{ $fmt($footer['hafalan']) }}</td>
            <td>{{ $fmt($footer['kepercayaan']) }}</td>
        </tr>
        @endif
    </tbody>
</table>

{{-- ── Grafik + tabel materi ── --}}
@php $progList = $programs->values(); @endphp
<table class="two-col">
    <tr>
        <td>
            <div class="section-title">Grafik Sesi per Bulan</div>
            @if(count($rows))
                @php $maxSesi = max(1, collect($rows)->max('sesi') ?: 1); @endphp
                <table class="bar-chart">
                    @foreach($rows as $r)
                        @php $pct = max(2, round(($r['sesi'] / $maxSesi) * 100)); @endphp
                        <tr>
                            <td class="bc-label">{{ $r['label'] }}</td>
                            <td class="bc-track">
                                <div class="bc-bar" style="width: {{ $pct }}%;">&nbsp;</div>
                            </td>
                            <td class="bc-val">{{ $r['sesi'] }} sesi</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div style="color:#888; font-size:9px;">Belum ada data sesi pada periode ini.</div>
            @endif
        </td>
        <td>
            @include('admin.evaluations._materi-table', ['title' => $progList[0] ?? null, 'list' => $materi[$progList[0] ?? ''] ?? []])
        </td>
    </tr>
    <tr>
        <td style="padding-top:10px;">
            <div class="section-title">Profil Kemampuan</div>
            {!! $radarSvg !!}
        </td>
        <td style="padding-top:10px;">
            @if(isset($progList[1]))
                @include('admin.evaluations._materi-table', ['title' => $progList[1], 'list' => $materi[$progList[1]] ?? []])
            @endif
        </td>
    </tr>
</table>

@if($progList->count() > 2)
    @foreach($progList->slice(2) as $prog)
        <div style="margin-top:8px;">
            @include('admin.evaluations._materi-table', ['title' => $prog, 'list' => $materi[$prog] ?? []])
        </div>
    @endforeach
@endif

{{-- ── Catatan & TTD ── --}}
<div style="margin-top:10px;">
    <div class="section-title">Catatan Tambahan :</div>
    <table class="two-col">
        <tr>
            <td style="width:60%;"><div class="catatan-box"></div></td>
            <td style="width:40%; text-align:center; vertical-align:top;">
                <div>Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div style="height:48px;"></div>
                <div style="font-weight:bold; border-top:1px solid #333; display:inline-block; padding-top:2px;">Pimpinan LIVO</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
