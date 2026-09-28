<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BAPSB {{ $bapsb->nomor }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 13px; text-align: center; margin-top: 0; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #444; padding: 4px 6px; }
        th { background: #f2f2f2; }
        .meta { margin: 12px 0; }
        .signatures { margin-top: 28px; width: 100%; }
        .signatures td { border: none; width: 25%; vertical-align: top; text-align: center; padding-top: 40px; }
        .sign-line { border-top: 1px solid #000; margin-top: 48px; padding-top: 4px; }
    </style>
</head>
<body onload="window.print()">
    <h1>BERITA ACARA PEMERIKSAAN SURAT BERHARGA BANK</h1>
    <h2>{{ $bapsb->nomor }}</h2>
    <div class="meta">
        <div>Project: <strong>{{ $bapsb->project }}</strong></div>
        <div>Period: <strong>{{ $bapsb->period }}</strong></div>
        <div>Date: <strong>{{ $bapsb->bapsb_date?->format('d M Y') }}</strong></div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Number</th>
                <th>Bank account</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Physical</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bapsb->lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->type }}</td>
                    <td>{{ $line->nomor }}</td>
                    <td>{{ $line->bank_account }}</td>
                    <td>{{ $line->bilyet_date?->format('d/m/Y') }}</td>
                    <td style="text-align:right">{{ number_format($line->amount, 0, ',', '.') }}</td>
                    <td>{{ $line->physical_present ? 'Ada' : 'Tidak ada' }}</td>
                    <td>{{ $line->location }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table style="margin-top:16px; width: 60%;">
        <tr><th colspan="2">Summary</th></tr>
        <tr><td>Bilyet Giro</td><td>{{ $bapsb->count_bg }} — Rp {{ number_format($bapsb->total_bg, 0, ',', '.') }}</td></tr>
        <tr><td>Checks</td><td>{{ $bapsb->count_cek }} — Rp {{ number_format($bapsb->total_cek, 0, ',', '.') }}</td></tr>
        <tr><td>LOA</td><td>{{ $bapsb->count_loa }} — Rp {{ number_format($bapsb->total_loa, 0, ',', '.') }}</td></tr>
        <tr><td>Settled (period)</td><td>{{ $bapsb->count_cair }}</td></tr>
        <tr><td>Voided (period)</td><td>{{ $bapsb->count_void }}</td></tr>
    </table>
    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line">Prepared by<br>{{ $bapsb->preparedBy?->name }}</div>
            </td>
            <td>
                <div class="sign-line">Checked by 1<br>{{ $bapsb->checker1 }}</div>
            </td>
            <td>
                <div class="sign-line">Checked by 2<br>{{ $bapsb->checker2 }}</div>
            </td>
            <td>
                <div class="sign-line">Approved by<br>{{ $bapsb->approved_by ?: '—' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
