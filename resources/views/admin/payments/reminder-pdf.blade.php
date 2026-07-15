<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Reminder Payment - {{ $student->full_name }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10.5px; color: #222; margin: 0; }

        .header-top { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .header-top td { vertical-align: middle; }
        .logo { height: 46px; }
        .title { text-align: center; font-size: 22px; font-weight: bold; color: #1a2fd6; letter-spacing: .5px; }
        .period-line { text-align: center; font-size: 11px; font-weight: bold; color: #222; margin-top: 2px; }
        .period-line .lbl { display: inline-block; width: 80px; text-align: left; }

        table.info { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.info td { padding: 7px 8px; vertical-align: top; font-size: 10.5px; border-bottom: 1px solid #eee; }
        table.info .lbl { font-weight: bold; width: 110px; white-space: nowrap; }
        table.info .sep { width: 10px; }
        table.info .highlight { background: #fdf1d6; }
        table.info .highlight .lbl, table.info .highlight .val { color: #c0392b; font-weight: bold; }

        table.note { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.note td { vertical-align: top; padding: 0; }
        table.note .lbl { width: 96px; font-weight: bold; color: #c0392b; font-style: italic; white-space: nowrap; }
        table.note ul { margin: 0; padding-left: 14px; color: #c0392b; font-style: italic; font-size: 9.5px; line-height: 1.7; }
        table.note li { margin-bottom: 2px; }
    </style>
</head>
<body>

<table class="header-top">
    <tr>
        <td style="width:100px;">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td>
        <td>
            <div class="title">REMINDER PAYMENT</div>
            <div class="period-line"><span class="lbl">PERIODE</span> : {{ $periode }}</div>
        </td>
        <td style="width:100px;"></td>
    </tr>
</table>

<table class="info">
    <tr>
        <td class="lbl">NIS</td><td class="sep">:</td>
        <td>{{ $student->nis ?? '-' }}{{ $student->nickname ? ' - ' . $student->nickname : '' }}</td>

        <td class="lbl">Jenis KBM</td><td class="sep">:</td>
        <td>{{ $kbm_process }}</td>

        <td class="lbl">No. Registrasi</td><td class="sep">:</td>
        <td>{{ $student->registration_code ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Nama</td><td class="sep">:</td>
        <td>{{ $student->full_name }}</td>

        <td class="lbl">Pilihan Program</td><td class="sep">:</td>
        <td>{{ $program_name }}</td>

        <td class="lbl">Tgl Mulai Belajar</td><td class="sep">:</td>
        <td>{{ $student->registration_date ? \Carbon\Carbon::parse($student->registration_date)->format('d/m/Y') : '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Periode Bergabung</td><td class="sep">:</td>
        <td>{{ $joined_label }}</td>

        <td class="lbl">Kuota Sesi</td><td class="sep">:</td>
        <td>{{ $quota_purchased }}</td>

        <td class="lbl">Masa Aktif (Hari)</td><td class="sep">:</td>
        <td>{{ $masa_aktif }}</td>
    </tr>
    <tr>
        <td class="lbl">Kelas/Tingkat</td><td class="sep">:</td>
        <td>{{ $student->grade ?? '-' }}</td>

        <td class="lbl">Paket Pembayaran</td><td class="sep">:</td>
        <td>{{ $period_label }}</td>

        <td class="lbl highlight">Tgl Expired</td><td class="sep highlight">:</td>
        <td class="highlight val">{{ $expired_date }}</td>
    </tr>
    <tr>
        <td class="lbl">Asal Sekolah</td><td class="sep">:</td>
        <td>{{ $student->school_origin ?? '-' }}</td>

        <td class="lbl">Sesi Digunakan</td><td class="sep">:</td>
        <td>{{ $quota_used }}</td>

        <td class="lbl highlight">Sisa Kuota</td><td class="sep highlight">:</td>
        <td class="highlight val">{{ $quota_remaining }}</td>
    </tr>
</table>

<table class="note">
    <tr>
        <td class="lbl">Note</td>
        <td>
            <ul>
                <li># Pembayaran wajib dilakukan ketika masa aktif mendekati masa tanggal expired, dan/atau sisa kuota sesi sudah akan habis.</li>
                <li># Jika pembayaran dilakukan sebelum masa tanggal expired, maka sisa Kuota belajar akan diakumulasikan ke periode berikutnya.</li>
                <li># Untuk pembayaran yang dilakukan sesudah masa tanggal expired, maka nominal pembayaran SPP akan mengikuti harga baru yang berlaku.</li>
            </ul>
        </td>
    </tr>
</table>

</body>
</html>
