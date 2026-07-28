<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $tutor->name }} - {{ $month->format('Y-m') }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #2C3E73; padding-bottom: 8px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 16px; color: #2C3E73; }
        .header p { margin: 2px 0 0; color: #666; }
        table.info { width: 100%; margin-bottom: 12px; }
        table.info td { padding: 3px 0; vertical-align: top; }
        table.detail { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.detail th, table.detail td { border: 1px solid #ccc; padding: 6px 8px; }
        table.detail th { background: #2C3E73; color: #fff; text-align: left; }
        .total-row td { font-weight: bold; background: #f0f4ff; }
        .text-end { text-align: right; }
        .footer { margin-top: 24px; width: 100%; }
        .footer td { width: 50%; text-align: center; }
        .sign-space { height: 55px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SLIP GAJI TUTOR</h1>
        <p>Bimbingan Belajar LIVO · Periode {{ $month->locale("id")->translatedFormat("F Y") }}</p>
    </div>

    <table class="info">
        <tr>
            <td width="20%"><strong>Nama Tutor</strong></td>
            <td width="45%">: {{ $tutor->name }}</td>
            <td width="20%"><strong>Tanggal Cetak</strong></td>
            <td>: {{ now()->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td><strong>No. Rekening</strong></td>
            <td>: {{ $tutor->no_rekening ?: '-' }}</td>
            <td><strong>Periode</strong></td>
            <td>: {{ $month->locale("id")->translatedFormat("F Y") }}</td>
        </tr>
    </table>

    @php $rp = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.'); @endphp
    <table class="detail">
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="text-end" width="18%">Jumlah</th>
                <th class="text-end" width="22%">Nominal</th>
                <th class="text-end" width="22%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Fee sesi mengajar (per slot tanggal + jam)</td>
                <td class="text-end">{{ $fee['session_count'] }} sesi</td>
                <td class="text-end">{{ $rp($tutor->fee_per_session ?? 0) }}</td>
                <td class="text-end">{{ $rp($fee['fee_session']) }}</td>
            </tr>
            <tr>
                <td>Fee siswa Privat (kehadiran)</td>
                <td class="text-end">{{ $fee['private_count'] }} siswa</td>
                <td class="text-end">{{ $rp($tutor->fee_per_student_private ?? 0) }}</td>
                <td class="text-end">{{ $rp($fee['fee_private']) }}</td>
            </tr>
            <tr>
                <td>Fee siswa Semi-Privat (kehadiran)</td>
                <td class="text-end">{{ $fee['regular_count'] }} siswa</td>
                <td class="text-end">{{ $rp($tutor->fee_per_student ?? 0) }}</td>
                <td class="text-end">{{ $rp($fee['fee_regular']) }}</td>
            </tr>
            <tr>
                <td>Fee transport (per hari mengajar)</td>
                <td class="text-end">{{ $fee['day_count'] }} hari</td>
                <td class="text-end">{{ $rp($tutor->fee_transport_per_day ?? 0) }}</td>
                <td class="text-end">{{ $rp($fee['fee_transport']) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">TOTAL DITERIMA</td>
                <td class="text-end">{{ $rp($fee['total']) }}</td>
            </tr>
        </tbody>
    </table>

    @if($fee['total'] <= 0)
        <p style="color:#b45309;"><em>Catatan: sebagian/seluruh tarif fee belum diatur oleh admin.</em></p>
    @endif

    <table class="footer">
        <tr>
            <td>
                Penerima,<br>
                <div class="sign-space"></div>
                <strong>{{ $tutor->name }}</strong>
            </td>
            <td>
                Hormat kami,<br>
                <div class="sign-space"></div>
                <strong>Admin LIVO</strong>
            </td>
        </tr>
    </table>
</body>
</html>
