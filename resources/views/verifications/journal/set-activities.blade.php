@extends('templates.main')

@section('title_page')
    Atur Kegiatan VJ
@endsection

@section('breadcrumb_title')
    verifications / journal / atur kegiatan
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-warning mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-tags"></i> Atur Kegiatan — {{ $vj->nomor }}
                </h3>
                <a href="{{ route('verifications.journal.preview', $vj->id) }}" class="vj-btn vj-btn-secondary">
                    <i class="fas fa-eye"></i> Preview Jurnal
                </a>
            </div>
            <form action="{{ route('verifications.journal.set_activities.store', $vj->id) }}" method="POST">
                @csrf
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Realisasi</th>
                                <th>Deskripsi Nota</th>
                                <th>Akun</th>
                                <th class="text-right">Amount</th>
                                <th>Kegiatan Saat Ini</th>
                                <th>Atur Kegiatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($details as $index => $detail)
                                <tr>
                                    <td>{{ $detail->realization?->nomor }}</td>
                                    <td>{{ $detail->description }}</td>
                                    <td>{{ $detail->account?->account_number }}</td>
                                    <td class="text-right">{{ number_format($detail->amount, 2) }}</td>
                                    <td>
                                        @if ($detail->activity_excluded)
                                            <span class="badge badge-secondary">Tanpa kegiatan</span>
                                        @elseif ($detail->effective_activity)
                                            <span class="badge badge-info">{{ $detail->effective_activity->code }}</span>
                                            {{ $detail->effective_activity->name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 280px;">
                                        <input type="hidden" name="assignments[{{ $index }}][realization_detail_id]" value="{{ $detail->id }}">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input activity-excluded-check"
                                                id="excluded-{{ $detail->id }}"
                                                name="assignments[{{ $index }}][activity_excluded]" value="1"
                                                {{ $detail->activity_excluded ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="excluded-{{ $detail->id }}">Tanpa kegiatan</label>
                                        </div>
                                        <select name="assignments[{{ $index }}][activity_id]" class="form-control form-control-sm select2 activity-select"
                                            data-row="{{ $detail->id }}">
                                            <option value="">— Ikut header / kosong —</option>
                                            @foreach ($activities as $activity)
                                                <option value="{{ $activity->id }}"
                                                    {{ (int) $detail->activity_id === (int) $activity->id ? 'selected' : '' }}>
                                                    {{ $activity->code }} — {{ $activity->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('verifications.journal.show', $vj->id) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Simpan & Regenerasi Jurnal
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function () {
            $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

            $('.activity-excluded-check').on('change', function () {
                const rowId = $(this).attr('id').replace('excluded-', '');
                const select = $('.activity-select[data-row="' + rowId + '"]');
                if ($(this).is(':checked')) {
                    select.val('').trigger('change').prop('disabled', true);
                } else {
                    select.prop('disabled', false);
                }
            }).trigger('change');
        });
    </script>
@endsection
