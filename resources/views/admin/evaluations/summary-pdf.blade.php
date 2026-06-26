<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Hasil Belajar - {{ $student->full_name }}</title>
    <style>
        @page { margin: 10mm 9mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #222; margin: 0; }
        .header-top { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .header-top td { vertical-align: middle; }
        .title { text-align: center; font-size: 20px; font-weight: bold; letter-spacing: .5px; }
        .subtitle { text-align: center; font-size: 10px; color: #444; }
        .period-line { text-align: center; font-size: 11px; font-weight: bold; color: #222; margin-top: 2px; }
        .logo { height: 46px; }
        table.info { width: 100%; border-collapse: collapse; margin: 6px 0 8px; font-size: 9.5px; }
        table.info td { padding: 3px 4px; vertical-align: top; }
        .info .lbl { font-weight: bold; white-space: nowrap; }
        .info .sep { width: 6px; }
        .badge { display: inline-block; padding: 1px 7px; color: #111; font-weight: bold; font-size: 9.5px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
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

        table.catatan-lines { width: 100%; border: 1px solid #777; border-collapse: collapse; }
        table.catatan-lines td { height: 22px; border-bottom: 1px solid #d4ddec; }
        table.catatan-lines tr:last-child td { border-bottom: none; }
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
    $predColor = $predikat === 'Amat Baik' ? '#86efac' : ($predikat === 'Baik' ? '#5eead4' : ($predikat === 'Cukup' ? '#fde68a' : '#fca5a5'));
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
            <div class="period-line">Periode : {{ $periode }}</div>
        </td>
        <td style="width:90px;"></td>
    </tr>
</table>

<table class="info">
    <tr>
        <td class="lbl">NIS / Nama Panggilan</td><td class="sep">:</td>
        <td>{{ $student->nis ?? '-' }}{{ $student->nickname ? ' ' . $student->nickname : '' }}</td>
        <td class="lbl">Kelas</td><td class="sep">:</td>
        <td>{{ $student->grade ?? '-' }}</td>
        <td class="lbl">Program</td><td class="sep">:</td>
        <td>{{ $student->package ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Nama Lengkap</td><td class="sep">:</td>
        <td>{{ $student->full_name }}</td>
        <td class="lbl">Asal Sekolah</td><td class="sep">:</td>
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

{{-- ── Tabel pembahasan (berdampingan, 2 per baris) ── --}}
@php $progList = $programs->values(); @endphp
<table class="two-col">
    @foreach($progList->chunk(2) as $pair)
    <tr>
        @foreach($pair as $prog)
        <td valign="top" style="padding-bottom:8px;">
            @include('admin.evaluations._materi-table', ['title' => $prog, 'list' => $materi[$prog] ?? []])
        </td>
        @endforeach
        @if($pair->count() < 2)<td valign="top"></td>@endif
    </tr>
    @endforeach
</table>

{{-- ── Grafik (di bawah tabel pembahasan, berdampingan) ── --}}
<table class="two-col">
    <tr>
        <td valign="top">
            <div class="section-title">Grafik Sesi per Bulan</div>
            @if(count($rows))
                <img src="data:image/svg+xml;base64,{{ base64_encode($sessionSvg) }}" style="width:100%; height:auto;">
            @else
                <div style="color:#888; font-size:9px;">Belum ada data sesi pada periode ini.</div>
            @endif
        </td>
        <td valign="top">
            <div class="section-title">Profil Kemampuan</div>
            @if(count($rows))
                <img src="data:image/svg+xml;base64,{{ base64_encode($abilitySvg) }}" style="width:100%; height:auto;">
            @else
                <div style="color:#888; font-size:9px;">Belum ada data pada periode ini.</div>
            @endif
        </td>
    </tr>
</table>

{{-- ── Catatan & TTD ── --}}
<div style="margin-top:8px;">
    <div class="section-title">Catatan Tambahan :</div>
    <table class="two-col">
        <tr>
            <td style="width:60%;" valign="top">
                <table class="catatan-lines">
                    @for($i = 0; $i < 7; $i++)
                        <tr><td>&nbsp;</td></tr>
                    @endfor
                </table>
            </td>
            <td style="width:40%; text-align:center; vertical-align:top;">
                <div>Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div style="height:64px;"></div>
                <div style="font-weight:bold; border-top:1px solid #333; display:inline-block; padding-top:2px;">Branch Manager</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
