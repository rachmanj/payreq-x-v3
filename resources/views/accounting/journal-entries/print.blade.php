<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Journal Voucher | {{ $journalEntry->number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #333; padding: 4px 6px; }
        th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .header { margin-bottom: 20px; }
        h2 { margin: 0 0 8px 0; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>JOURNAL VOUCHER</h2>
        <p><strong>Number:</strong> {{ $journalEntry->number }}
            @if ($journalEntry->sap_journal_no)
                | <strong>SAP No:</strong> {{ $journalEntry->sap_journal_no }}
            @endif
        </p>
        <p><strong>Date:</strong> {{ $journalEntry->date?->format('d-M-Y') }}
            | <strong>Reference:</strong> {{ $journalEntry->reference ?? '—' }}</p>
        <p><strong>Memo:</strong> {{ $journalEntry->memo ?? '—' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Account</th>
                <th>Account Name</th>
                <th>Project</th>
                <th>Cost Center</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($journalEntry->lines as $line)
                <tr>
                    <td>{{ $line->line_no }}</td>
                    <td>{{ $line->account_code }}</td>
                    <td>{{ $accountNames[$line->account_code] ?? '' }}</td>
                    <td>{{ $line->project }}</td>
                    <td>{{ $line->cost_center }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="text-right">{{ $line->debit_credit === 'debit' ? number_format($line->amount, 2) : '' }}</td>
                    <td class="text-right">{{ $line->debit_credit === 'credit' ? number_format($line->amount, 2) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">Total</th>
                <th class="text-right">{{ number_format($journalEntry->totalDebit(), 2) }}</th>
                <th class="text-right">{{ number_format($journalEntry->totalCredit(), 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 24px;">
        <strong>Prepared by:</strong> {{ $journalEntry->createdBy?->name }}
        @if ($journalEntry->sapSubmittedBy)
            | <strong>Posted by:</strong> {{ $journalEntry->sapSubmittedBy->name }}
        @endif
    </p>
</body>
</html>
