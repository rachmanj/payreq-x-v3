<table>
    <thead>
        <tr>
            <th>Section</th>
            <th>Category</th>
            <th>Date</th>
            <th>Description</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Header</td>
            <td>Account</td>
            <td></td>
            <td>{{ $bankReconciliation->giro?->acc_no }} {{ $bankReconciliation->giro?->acc_name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Header</td>
            <td>Bank</td>
            <td></td>
            <td>{{ $bankReconciliation->giro?->bank?->name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Header</td>
            <td>Period</td>
            <td></td>
            <td>{{ $bankReconciliation->periode?->format('F Y') }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Header</td>
            <td>Status</td>
            <td></td>
            <td>{{ $bankReconciliation->status }} / {{ $bankReconciliation->validation_status }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Bank statement</td>
            <td>Closing balance</td>
            <td></td>
            <td></td>
            <td>{{ $statement['closing_balance_bank'] !== null ? number_format($statement['closing_balance_bank'], 2, '.', '') : '' }}</td>
        </tr>
        @foreach ($statement['book_items'] ?? [] as $category => $items)
            @foreach ($items as $item)
                <tr>
                    <td>Bank adjustments</td>
                    <td>{{ $category }}</td>
                    <td>{{ $item['date'] ?? '' }}</td>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td>{{ number_format($item['net'], 2, '.', '') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td>Bank statement</td>
            <td>Adjusted bank</td>
            <td></td>
            <td></td>
            <td>{{ $statement['adjusted_bank'] !== null ? number_format($statement['adjusted_bank'], 2, '.', '') : '' }}</td>
        </tr>
        <tr>
            <td>Books (SAP)</td>
            <td>Closing balance</td>
            <td></td>
            <td></td>
            <td>{{ $statement['closing_balance_book'] !== null ? number_format($statement['closing_balance_book'], 2, '.', '') : '' }}</td>
        </tr>
        @foreach ($statement['bank_items'] ?? [] as $category => $items)
            @foreach ($items as $item)
                <tr>
                    <td>Book adjustments</td>
                    <td>{{ $category }}</td>
                    <td>{{ $item['date'] ?? '' }}</td>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td>{{ number_format(-1 * $item['net'], 2, '.', '') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td>Books (SAP)</td>
            <td>Adjusted book</td>
            <td></td>
            <td></td>
            <td>{{ $statement['adjusted_book'] !== null ? number_format($statement['adjusted_book'], 2, '.', '') : '' }}</td>
        </tr>
        <tr>
            <td>Summary</td>
            <td>Unexplained difference</td>
            <td></td>
            <td></td>
            <td>{{ $statement['unexplained_difference'] !== null ? number_format($statement['unexplained_difference'], 2, '.', '') : '' }}</td>
        </tr>
        <tr>
            <td>Sign-off</td>
            <td>Prepared by</td>
            <td>{{ $bankReconciliation->created_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $bankReconciliation->creator?->name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Sign-off</td>
            <td>Submitted by</td>
            <td>{{ $bankReconciliation->submitted_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $bankReconciliation->submittedBy?->name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Sign-off</td>
            <td>Validated by</td>
            <td>{{ $bankReconciliation->validated_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $bankReconciliation->validatedBy?->name }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
