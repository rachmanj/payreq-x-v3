<table class="table table-bordered">
    <thead>
        <tr>
            <th class="text-right">Kopur</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-right">1.000</td>
            <td class="text-right">{{ $pcbc->coin_seribu }}</td>
            <td class="text-right">{{ number_format($pcbc->coin_seribu * 1000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">500</td>
            <td class="text-right">{{ $pcbc->coin_lima_ratus }}</td>
            <td class="text-right">{{ number_format($pcbc->coin_lima_ratus * 500, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">200</td>
            <td class="text-right">{{ $pcbc->coin_dua_ratus }}</td>
            <td class="text-right">{{ number_format($pcbc->coin_dua_ratus * 200, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">100</td>
            <td class="text-right">{{ $pcbc->coin_seratus }}</td>
            <td class="text-right">{{ number_format($pcbc->coin_seratus * 100, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">50</td>
            <td class="text-right">{{ $pcbc->coin_lima_puluh }}</td>
            <td class="text-right">{{ number_format($pcbc->coin_lima_puluh * 50, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">25</td>
            <td class="text-right">{{ $pcbc->coin_dua_puluh_lima }}</td>
            <td class="text-right">{{ number_format($pcbc->coin_dua_puluh_lima * 25, 0) }}</td>
        </tr>
        <tr><td colspan="3">-</td></tr>
        <tr><td colspan="3">-</td></tr>
        <tr><td colspan="3">-</td></tr>
        <tr>
            <th class="text-right" colspan="2">Jumlah uang logam</th>
            <th class="text-right">{{ number_format($uang_logam_total, 0) }}</th>
        </tr>
    </tbody>
</table>