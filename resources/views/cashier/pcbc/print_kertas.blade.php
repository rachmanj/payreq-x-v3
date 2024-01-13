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
            <td class="text-right">100.000</td>
            <td class="text-right">{{ $pcbc->seratus_ribu }}</td>
            <td class="text-right">{{ number_format($pcbc->seratus_ribu * 100000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">50.000</td>
            <td class="text-right">{{ $pcbc->lima_puluh_ribu }}</td>
            <td class="text-right">{{ number_format($pcbc->lima_puluh_ribu * 50000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">20.000</td>
            <td class="text-right">{{ $pcbc->dua_puluh_ribu }}</td>
            <td class="text-right">{{ number_format($pcbc->dua_puluh_ribu * 20000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">10.000</td>
            <td class="text-right">{{ $pcbc->sepuluh_ribu }}</td>
            <td class="text-right">{{ number_format($pcbc->sepuluh_ribu * 10000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">5.000</td>
            <td class="text-right">{{ $pcbc->lima_ribu }}</td>
            <td class="text-right">{{ number_format($pcbc->lima_ribu * 5000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">2.000</td>
            <td class="text-right">{{ $pcbc->dua_ribu }}</td>
            <td class="text-right">{{ number_format($pcbc->dua_ribu * 2000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">1.000</td>
            <td class="text-right">{{ $pcbc->seribu }}</td>
            <td class="text-right">{{ number_format($pcbc->seribu * 1000, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">500</td>
            <td class="text-right">{{ $pcbc->lima_ratus }}</td>
            <td class="text-right">{{ number_format($pcbc->lima_ratus * 500, 0) }}</td>
        </tr>
        <tr>
            <td class="text-right">100</td>
            <td class="text-right">{{ $pcbc->seratus }}</td>
            <td class="text-right">{{ number_format($pcbc->seratus * 100, 0) }}</td>
        </tr>
        <tr>
            <th class="text-right" colspan="2">Jumlah uang kertas</th>
            <th class="text-right">{{ number_format($uang_kertas_total, 0) }}</th>
        </tr>
    </tbody>
</table>