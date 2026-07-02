@php
    $brId = $month['reconciliation_id'] ?? null;
    $brStatus = $month['reconciliation_status'] ?? null;
    $brValidation = $month['reconciliation_validation_status'] ?? null;
@endphp

<div class="koran-recon-detail">
    <h6 class="text-muted mb-2"><i class="fas fa-balance-scale mr-1"></i> Bank Reconciliation</h6>

    @if (
        $brId &&
            $brStatus === \App\Models\BankReconciliation::STATUS_COMPLETED &&
            $brValidation === \App\Models\BankReconciliation::VALIDATION_VALIDATED)
        <p class="mb-2">
            <span class="badge badge-success"><i class="fas fa-check-double mr-1"></i> Selesai &amp; tervalidasi</span>
        </p>
        <a href="{{ route('cashier.bank-reconciliation.report', $brId) }}" class="btn btn-sm btn-success">
            <i class="fas fa-file-alt mr-1"></i> Lihat laporan rekonsiliasi
        </a>
    @elseif ($brId && $brValidation === \App\Models\BankReconciliation::VALIDATION_PENDING)
        <p class="mb-2">
            <span class="badge badge-purple"><i class="fas fa-user-check mr-1"></i> Menunggu validasi</span>
        </p>
        <a href="{{ route('cashier.bank-reconciliation.show', $brId) }}" class="btn btn-sm btn-outline-purple">
            <i class="fas fa-eye mr-1"></i> Buka rekonsiliasi
        </a>
    @elseif ($brId && $brValidation === \App\Models\BankReconciliation::VALIDATION_REJECTED)
        <p class="mb-2">
            <span class="badge badge-danger"><i class="fas fa-undo mr-1"></i> Ditolak — perlu perbaikan</span>
        </p>
        <a href="{{ route('cashier.bank-reconciliation.show', $brId) }}" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-edit mr-1"></i> Perbaiki rekonsiliasi
        </a>
    @elseif ($brId && $brStatus === \App\Models\BankReconciliation::STATUS_FAILED)
        <p class="mb-2">
            <span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memproses</span>
        </p>
        <a href="{{ route('cashier.bank-reconciliation.show', $brId) }}" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-redo mr-1"></i> Buka &amp; parse ulang
        </a>
    @elseif ($brId && $brStatus === \App\Models\BankReconciliation::STATUS_PROCESSING)
        <p class="mb-2">
            <span class="badge badge-warning"><i class="fas fa-spinner fa-spin mr-1"></i> Sedang diproses</span>
        </p>
        <a href="{{ route('cashier.bank-reconciliation.show', $brId) }}" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-eye mr-1"></i> Lihat progress
        </a>
    @elseif (
        $brId &&
            in_array($brStatus, [
                \App\Models\BankReconciliation::STATUS_IN_REVIEW,
                \App\Models\BankReconciliation::STATUS_DRAFT,
            ], true))
        <p class="mb-2">
            <span class="badge badge-info"><i class="fas fa-tasks mr-1"></i> Dalam review</span>
        </p>
        <a href="{{ route('cashier.bank-reconciliation.show', $brId) }}" class="btn btn-sm btn-outline-info">
            <i class="fas fa-tasks mr-1"></i> Lanjutkan pencocokan
        </a>
    @else
        <p class="mb-2">
            <span class="badge badge-secondary"><i class="fas fa-balance-scale mr-1"></i> Belum dimulai</span>
        </p>
        <a href="{{ route('cashier.bank-reconciliation.create', [
            'giro_id' => $giro['giro_id'],
            'dokumen_id' => $month['dokumen_id'],
            'periode' => $year . '-' . $month['month'] . '-01',
        ]) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-play mr-1"></i> Mulai rekonsiliasi
        </a>
    @endif
</div>
