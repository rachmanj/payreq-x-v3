<div class="col-12">
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">PC Balance Check</h3>
        </div>
      <div class="card-body">
        <table class="table table-striped">
          <tbody>
            <tr>
              <td>A. Saldo PC Payreq System</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $dashboard_report['saldo_pc_payreq_system'] }}</strong></td>
              <td></td>
            </tr>
            <tr>
              <td>B. Payreq belum realisasi</td>
              <td class="text-right">Rp. {{ $dashboard_report['payreq_belum_realisasi_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>C. Realisasi belum verifikasi</td>
              <td class="text-right">Rp. {{ $dashboard_report['realisasi_belum_verifikasi_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>D. Verifikasi belum posted</td>
              <td class="text-right">Rp. {{ $dashboard_report['verifikasi_belum_posted_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>E. Variance Realisasi belum incoming</td>
              <td class="text-right">Rp. {{ $dashboard_report['variance_realisasi_belum_incoming_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>F .Variance Realisasi belum outgoing</td>
                <td class="text-right" style="color: red;">(Rp. {{ $dashboard_report['variance_realisasi_belum_outgoing_amount'] }})</td>
              <td></td>
            </tr>
            <tr>
              <td>G. Total Advance Employee (B + C + D + E - F)</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $dashboard_report['total_advance_employee'] }}</strong></td>
              <td></td>
            </tr>
            <tr>
              <td>H. Cek balance PC SAP (A + G)</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $dashboard_report['cek_balance_pc_sap'] }}</strong></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div> 
  </div>