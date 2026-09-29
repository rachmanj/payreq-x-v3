@php
    $report = $dashboard_report;
    $varianceLevel = $report['selisih_cek_balance_sap_level'] ?? null;
    $varianceClass = \App\Support\PettyCashBalanceVariance::bootstrapTextClass($varianceLevel);
@endphp
<div class="col-12">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0"><i class="fas fa-calculator"></i> Pengecekan Saldo PC</h3>
            <form action="{{ route('cashier.dashboard.pc_sap_balance.refresh') }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync-alt"></i> Refresh saldo SAP
                </button>
            </form>
        </div>
      <div class="card-body">
        <table class="table table-striped">
          <tbody>
            <tr>
              <td>A. Saldo PC Payreq System</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $report['saldo_pc_payreq_system'] }}</strong></td>
              <td></td>
            </tr>
            <tr>
              <td>B. Payreq belum realisasi</td>
              <td class="text-right">Rp. {{ $report['payreq_belum_realisasi_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>C. Realisasi belum verifikasi</td>
              <td class="text-right">Rp. {{ $report['realisasi_belum_verifikasi_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>D. Verifikasi belum posted</td>
              <td class="text-right">Rp. {{ $report['verifikasi_belum_posted_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>E. Variance Realisasi belum incoming</td>
              <td class="text-right">Rp. {{ $report['variance_realisasi_belum_incoming_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>F .Variance Realisasi belum outgoing</td>
                <td class="text-right" style="color: red;">(Rp. {{ $report['variance_realisasi_belum_outgoing_amount'] }})</td>
              <td></td>
            </tr>
            <tr>
              <td>G. Total Advance Employee (B + C + D + E - F)</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $report['total_advance_employee'] }}</strong></td>
              <td></td>
            </tr>
            <tr>
              <td>H. Cek balance PC SAP (A + G)</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $report['cek_balance_pc_sap'] }}</strong></td>
              <td></td>
            </tr>
            <tr>
              <td>I. Saldo PC SAP (real-time)
                @if (!empty($report['saldo_pc_sap_account']))
                  <br><small class="text-muted">Akun SAP {{ $report['saldo_pc_sap_account'] }}</small>
                @endif
              </td>
              <td></td>
              <td class="text-right">
                @if ($report['saldo_pc_sap_realtime_available'] ?? false)
                  <strong>Rp. {{ $report['saldo_pc_sap_realtime'] }}</strong>
                @else
                  <span class="text-muted">tidak tersedia</span>
                @endif
              </td>
              <td></td>
            </tr>
            <tr>
              <td>J. Selisih (Cek balance - SAP)</td>
              <td></td>
              <td class="text-right {{ $varianceClass }}">
                @if (($report['selisih_cek_balance_sap'] ?? null) !== null)
                  <strong>Rp. {{ $report['selisih_cek_balance_sap'] }}</strong>
                @else
                  <span class="text-muted">tidak tersedia</span>
                @endif
              </td>
              <td></td>
            </tr>
          </tbody>
        </table>
        <p class="text-muted small mb-0">
          Hijau: selisih &le; Rp 1.000 &middot; Kuning: &le; Rp 1.000.000 &middot; Merah: di atas itu.
          Saldo SAP di-cache 5 menit; gunakan tombol refresh untuk memaksa ambil ulang.
        </p>
      </div>
    </div> 
  </div>
