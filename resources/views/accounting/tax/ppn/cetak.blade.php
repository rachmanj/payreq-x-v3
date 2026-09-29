<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan PPN {{ $masa }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 4px 6px; }
        th { background: #f0f0f0; }
        .text-right { text-align: right; }
        h2 { margin: 0 0 8px 0; }
    </style>
</head>
<body onload="window.print()">
    <h2>Ringkasan Monitoring PPN — Masa {{ $masa }}</h2>
    <p>
        Status: {{ $period->status }} |
        Disiapkan: {{ $period->prepared_at?->format('d-M-Y H:i') ?? '—' }}
        ({{ $period->preparedBy?->name ?? '—' }}) |
        Disetujui: {{ $period->approved_at?->format('d-M-Y H:i') ?? '—' }}
        ({{ $period->approvedBy?->name ?? '—' }})
    </p>

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="text-right">SAP / Snapshot</th>
                <th class="text-right">Aplikasi (fakturs)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>PPN Keluaran (PK)</td>
                <td class="text-right">{{ number_format((float) ($period->pk_total ?? 0), 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($appPk, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>PPN Masukan (PM)</td>
                <td class="text-right">{{ number_format((float) ($period->pm_total ?? 0), 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($appPm, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kurang bayar / Lebih bayar</td>
                <td class="text-right" colspan="2">{{ $period->kb_lb !== null ? number_format((float) $period->kb_lb, 0, ',', '.') : '—' }}</td>
            </tr>
            <tr>
                <td>Selisih SAP ↔ Aplikasi</td>
                <td class="text-right" colspan="2">{{ $period->diff_sap_app !== null ? number_format((float) $period->diff_sap_app, 0, ',', '.') : '—' }}</td>
            </tr>
            <tr>
                <td>Selisih Coretax ↔ Aplikasi</td>
                <td class="text-right" colspan="2">
                    @if ($period->diff_coretax_app !== null)
                        {{ number_format((float) $period->diff_coretax_app, 0, ',', '.') }}
                    @else
                        Belum tersedia
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <p class="text-muted" style="margin-top:24px;">
        Dokumen arsip SPT — dicetak {{ now()->format('d-M-Y H:i') }}.
        Snapshot rekonsiliasi: {{ $period->snapshot_json['reconciled_at'] ?? '—' }}.
    </p>
</body>
</html>
