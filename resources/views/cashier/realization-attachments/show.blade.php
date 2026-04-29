@extends('templates.main')

@section('title_page')
    Realization Attachments
@endsection

@section('breadcrumb_title')
    cashier / realization attachments / {{ $realization->nomor }}
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('cashier.realization-attachments.index') }}" class="btn btn-sm btn-secondary"><i
                    class="fas fa-arrow-left"></i> Back to list</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Realization</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Realization No</dt>
                        <dd class="col-sm-7">{{ $realization->nomor }}</dd>
                        <dt class="col-sm-5">Project</dt>
                        <dd class="col-sm-7">{{ $realization->project }}</dd>
                        <dt class="col-sm-5">Payreq No</dt>
                        <dd class="col-sm-7">{{ $realization->payreq->nomor ?? '' }}</dd>
                        <dt class="col-sm-5">Payreq requestor</dt>
                        <dd class="col-sm-7">{{ $realization->payreq->requestor->name ?? '' }}</dd>
                        <dt class="col-sm-5">Realization creator</dt>
                        <dd class="col-sm-7">{{ $realization->requestor->name ?? '' }}</dd>
                        <dt class="col-sm-5">Remarks</dt>
                        <dd class="col-sm-7 text-break">{!! $realization->remarks ? nl2br(e($realization->remarks)) : '—' !!}</dd>
                    </dl>
                </div>
            </div>
        </div>
        @can('create_realization_attachments')
            <div class="col-md-6">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">Upload file</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('cashier.realization-attachments.attachments.store', $realization) }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <input type="file" name="file" class="form-control-file @error('file') is-invalid @enderror"
                                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*" required>
                                @error('file')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Images or PDF, max 10 MB.</small>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload"></i>
                                Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Attachments</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th class="text-right">Size</th>
                                <th>Uploaded by</th>
                                <th>Uploaded at</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($realization->attachments as $attachment)
                                <tr>
                                    <td>{{ $attachment->original_name }}</td>
                                    <td class="text-right">
                                        @if ($attachment->size)
                                            {{ number_format($attachment->size / 1024, 1) }} KB
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $attachment->creator->name ?? '' }}</td>
                                    <td>{{ $attachment->created_at?->format('d-M-Y H:i') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('cashier.realization-attachments.attachments.download', $attachment) }}"
                                            class="btn btn-xs btn-info"><i class="fas fa-download"></i> Download</a>
                                        @can('delete_realization_attachments')
                                            @if ((int) $attachment->created_by === (int) auth()->id())
                                                <form
                                                    action="{{ route('cashier.realization-attachments.attachments.destroy', $attachment) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Delete this attachment?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-danger"><i
                                                            class="fas fa-trash"></i> Delete</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No attachments yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
