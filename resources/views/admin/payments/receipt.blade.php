<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Receipt LIVO - {{ $payment->no_payment }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Cinzel:wght@700;900&family=Lato:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --blue: #1a3a6e;
      --yellow: #f5c518;
      --border: #b0b8c4;
    }

    /* ── A5 = half of A4 = 148mm × 210mm ── */
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #d0d5de;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 32px 16px 48px;
      font-family: 'Lato', sans-serif;
    }

    /* Print button */
    .print-bar {
      width: 148mm;
      display: flex;
      justify-content: flex-end;
      margin-bottom: 10px;
    }

    .btn-print {
      background: var(--blue);
      color: #fff;
      border: none;
      padding: 8px 20px;
      border-radius: 5px;
      font-family: 'Lato', sans-serif;
      font-weight: 700;
      font-size: 0.82rem;
      letter-spacing: 0.04em;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 7px;
      box-shadow: 0 3px 10px rgba(26,58,110,0.28);
      transition: background .2s, transform .15s;
    }
    .btn-print:hover { background: #122a55; transform: translateY(-1px); }
    .btn-print svg { width: 15px; height: 15px; fill: currentColor; }

    /* Receipt */
    .receipt {
      width: 148mm;
      min-height: 210mm;
      background: #fff;
      border: 1.5px solid var(--border);
      padding: 7mm 8mm 6mm;
      box-shadow: 0 8px 28px rgba(0,0,0,0.13);
      display: flex;
      flex-direction: column;
    }

    /* ── HEADER ── */
    .hdr {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      padding-bottom: 5px;
      border-bottom: 2.5px solid var(--blue);
      margin-bottom: 7px;
    }

    /* Logo */
    .logo { display: flex; flex-direction: column; }
    .logo-wordmark {
      font-family: 'Cinzel', serif;
      font-weight: 900;
      font-size: 1.55rem;
      color: var(--blue);
      letter-spacing: -0.5px;
      line-height: 1;
      position: relative;
      display: inline-flex;
      align-items: flex-start;
    }
    .logo-wordmark .dot {
      width: 7px; height: 7px;
      background: var(--yellow);
      border-radius: 50%;
      margin-top: 3px;
      margin-left: 1px;
    }
    .logo-sub {
      font-size: 0.58rem;
      color: var(--blue);
      font-style: italic;
      letter-spacing: 0.06em;
      margin-top: 1px;
    }

    /* Title centre */
    .title-center {
      text-align: center;
      flex: 1;
      padding: 0 10px;
    }
    .title-main {
      font-family: 'Cinzel', serif;
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      color: #111;
    }
    .title-sub {
      font-size: 0.58rem;
      font-weight: 700;
      color: #444;
      margin-top: 1px;
    }

    /* Meta date/number */
    .meta { font-size: 0.62rem; text-align: right; }
    .meta table { margin-left: auto; }
    .meta td { padding: 1.5px 3px; color: #222; white-space: nowrap; }
    .meta td:first-child { font-weight: 700; }
    .rec-num {
      background: var(--yellow);
      font-weight: 700;
      padding: 0px 5px;
      border-radius: 2px;
      font-size: 0.62rem;
    }

    /* ── BODY ROWS ── */
    .rows { flex: 1; }

    .row {
      display: grid;
      gap: 0 16px;
      border-bottom: 1px dashed #c4c9d2;
      padding: 5px 0 4px;
    }
    .row.col2 { grid-template-columns: 1fr 1fr; }
    .row.col1 { grid-template-columns: 1fr; }

    .cell {}

    .lbl {
      font-size: 0.60rem;
      font-style: italic;
      font-weight: 700;
      color: #555;
      line-height: 1.25;
    }
    .lbl small {
      display: block;
      font-size: 0.56rem;
      font-weight: 400;
      color: #888;
    }

    .val-wrap {
      display: flex;
      align-items: baseline;
      gap: 5px;
      margin-top: 1px;
    }
    .colon { font-size: 0.72rem; font-weight: 700; color: #555; }
    .val {
      font-style: italic;
      font-weight: 700;
      font-size: 0.75rem;
      color: #111;
    }

    /* Amount in words */
    .val-script {
      font-family: 'Dancing Script', cursive;
      font-size: 1.25rem;
      font-weight: 700;
      color: #111;
    }

    /* Amount box */
    .amount-box {
      border: 1.5px solid #333;
      display: inline-block;
      padding: 3px 12px;
      font-family: 'Cinzel', serif;
      font-size: 0.95rem;
      font-weight: 700;
      color: #111;
      margin-top: 2px;
      letter-spacing: 0.02em;
    }

    /* ── FOOTER ── */
    .footer { margin-top: 8px; }

    .thankyou {
      text-align: center;
      font-size: 0.58rem;
      font-style: italic;
      color: #555;
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      padding: 4px 0;
      margin-bottom: 8px;
    }

    .footer-bottom {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
    }

    .address {
      font-size: 0.58rem;
      font-style: italic;
      font-weight: 700;
      color: #555;
      line-height: 1.7;
    }

    .sig-side { text-align: right; }
    .date-city {
      font-size: 0.65rem;
      font-style: italic;
      font-weight: 700;
      color: #333;
      margin-bottom: 6px;
    }
    .sig-name {
      display: inline-block;
      font-size: 0.66rem;
      font-weight: 700;
      color: #111;
      border-top: 1px solid #333;
      padding-top: 2px;
      margin-top: 2px;
    }

    /* ── PRINT ── */
    @media print {
      @page {
        size: 148mm 210mm;
        margin: 0;
      }
      body {
        background: #fff !important;
        padding: 0 !important;
      }
      .print-bar { display: none !important; }
      .receipt {
        width: 148mm;
        min-height: 210mm;
        box-shadow: none !important;
        border: none !important;
        padding: 7mm 8mm 6mm;
      }
    }
  </style>
</head>
<body>

  <div class="print-bar">
    <button class="btn-print" onclick="window.print()">
      <svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v5h4v4h12v-4h4v-5c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
      Cetak / Print
    </button>
  </div>

  <div class="receipt">

    <!-- HEADER -->
    <div class="hdr">
      <div class="logo">
        <div class="logo-wordmark" >
          <img src="{{ asset('frontend/images/logo.jpeg') }}" alt="logo" srcset="" style="width: 100px;">
        </div>
      </div>

      <div class="title-center">
        <div class="title-main">OFFICIAL RECEIPT</div>
        <div class="title-sub">(TANDA BUKTI PEMBAYARAN)</div>
      </div>

      <div class="meta">
        <table>
          <tr>
            <td>Date</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
          </tr>
          <tr>
            <td>Number</td>
            <td>:</td>
            <td><span class="rec-num">{{ $payment->no_payment }}</span></td>
          </tr>
        </table>
      </div>
    </div>

    <!-- BODY -->
    <div class="rows">

      <!-- Row 1 -->
      <div class="row col2">
        <div class="cell">
          <div class="lbl">Amount Received From <small>(Telah Diterima Dari)</small></div>
          <div class="val-wrap"><span class="val">{{ $payment->from }}</span></div>
        </div>
        <div class="cell">
          <div class="lbl">ID Number <small>(Nomor Induk Siswa)</small></div>
          <div class="val-wrap"><span class="val">{{ $payment->student->nis ?? $payment->student->registration_code ?? '-' }}</span></div>
        </div>
      </div>

      <!-- Row 2 -->
      <div class="row col2">
        <div class="cell">
          <div class="lbl">Student Name <small>(Nama Siswa)</small></div>
          <div class="val-wrap"><span class="val">{{ $payment->student->full_name ?? '-' }}</span></div>
        </div>
        <div class="cell">
          <div class="lbl">Kuota &amp; Masa Aktif</div>
          <div class="val-wrap"><span class="val">{{ $payment->quota ?? '-' }} Sesi | {{ $payment->expired_date ? \Carbon\Carbon::parse($payment->payment_date)->diffInDays(\Carbon\Carbon::parse($payment->expired_date)) : '-' }} Hari</span></div>
          <div style="margin-top:4px;">
            <div class="lbl">Tanggal Expired</div>
            <div class="val-wrap"><span class="val">{{ $payment->expired_date ? \Carbon\Carbon::parse($payment->expired_date)->format('d/m/Y') : '-' }}</span></div>
          </div>
        </div>
      </div>

      <!-- Row 3: Amount in Words -->
      <div class="row col1">
        <div class="cell">
          <div class="lbl">The Sum of Rupiah (in Words) <small>(Terbilang dalam Rupiah)</small></div>
          <div class="val-wrap" style="margin-top:2px;">
            
            <span class="val-script">{{ $terbilang }}</span>
          </div>
        </div>
      </div>

      <!-- Row 4: Purpose -->
      <div class="row col1">
        <div class="cell">
          <div class="lbl">Purpose of Payment <small>(Tujuan Pembayaran)</small></div>
          <div class="val-wrap" style="margin-top:2px;">
            
            <span class="val" style="line-height:1.6;">
              {{ $payment->description }}<br>
              @if($payment->student)
              Untuk Tingkat {{ $payment->student->grade ?? '-' }}
              @endif
            </span>
          </div>
        </div>
      </div>

      <!-- Row 5: Received By + Payment Method -->
      <div class="row col2">
        <div class="cell">
          <div class="lbl">Amount Received By <small>(Di Terima Oleh)</small></div>
          <div class="val-wrap"><span class="val">{{ $payment->receiver ?? '-' }}</span></div>
        </div>
        <div class="cell">
          <div class="lbl">Metode Pembayaran <small>(Payment Method)</small></div>
          <div class="val-wrap"><span class="val">{{ $payment->payment_method ?? '-' }}</span></div>
        </div>
      </div>

      <!-- Row 6: Amount Box -->
      <div class="row col1">
        <div class="cell">
          <div class="lbl">Amount <small>(Sejumlah)</small></div>
          <div class="val-wrap" style="margin-top:3px;">
            
            <span class="amount-box">Rp &nbsp;{{ number_format($amount, 0, ',', '.') }}</span>
          </div>
        </div>
      </div>

    </div><!-- /rows -->

    <!-- FOOTER -->
    <div class="footer">
      <div class="thankyou">
        ====== Thank you for your payment (Terima kasih atas pembayaran Anda) ======
      </div>

      <div class="footer-bottom">
        <div class="address">
          Bimbel LIVO Cabang Shibi, Jagakarsa<br>
          Jl. Shibi 4 No. 38, Srengseng Sawah<br>
          Phone : +62811 8179 511 | livo.id
        </div>

        <div class="sig-side">
          <div class="date-city">Jakarta, {{ \Carbon\Carbon::parse($payment->payment_date)->translatedFormat('d F Y') }}</div>
          @if(!empty($qrCode))
            <img src="{{ $qrCode }}" alt="QR" style="width:60px;height:60px;display:block;margin-left:auto;margin-bottom:6px;">
          @else
            <div style="height:60px;"></div>
          @endif
          <div class="sig-name">Branch Manager</div>
        </div>
      </div>
    </div>

  </div><!-- /receipt -->

</body>
</html>