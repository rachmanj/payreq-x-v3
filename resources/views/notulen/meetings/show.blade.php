@extends('templates.main')

@section('title_page')
    {{ $meeting->title }}
@endsection

@section('breadcrumb_title')
    notulen / documents / {{ Str::limit($meeting->title, 40) }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center flex-wrap">
                    <h3 class="card-title mb-0 mr-3">{{ $meeting->title }}</h3>
                    @php
                        $badge = match ($meeting->status) {
                            \App\Models\Meeting::STATUS_PROCESSED => 'success',
                            \App\Models\Meeting::STATUS_FAILED => 'danger',
                            default => 'warning',
                        };
                    @endphp
                    <span class="badge badge-{{ $badge }}">{{ ucfirst($meeting->status) }}</span>
                    <a href="{{ route('notulen.meetings.download', $meeting) }}"
                        class="btn btn-outline-danger btn-sm ml-auto" target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf mr-1"></i> Download PDF
                    </a>
                </div>
                <div class="card-body">
                    <dl class="row mb-4">
                        <dt class="col-sm-3">Meeting Date</dt>
                        <dd class="col-sm-9">{{ $meeting->meeting_date?->format('d F Y') ?? '-' }}</dd>

                        <dt class="col-sm-3">Original File</dt>
                        <dd class="col-sm-9">{{ $meeting->original_filename }}</dd>

                        <dt class="col-sm-3">Uploaded By</dt>
                        <dd class="col-sm-9">{{ $meeting->uploader->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Uploaded At</dt>
                        <dd class="col-sm-9">{{ $meeting->created_at?->format('d-M-Y H:i') }}</dd>
                    </dl>

                    <h5>Extracted Text</h5>
                    @if ($meeting->status === \App\Models\Meeting::STATUS_PENDING)
                        <p class="text-muted">Dokumen sedang diproses…</p>
                    @elseif ($meeting->status === \App\Models\Meeting::STATUS_FAILED)
                        <p class="text-danger">Gagal memproses PDF. PDF scan/gambar akan dicoba via OCR otomatis — pastikan
                            <code>OPENROUTER_API_KEY</code> sudah dikonfigurasi, lalu klik <strong>Proses ulang</strong>.</p>
                        @can('upload_notulen')
                            <form method="POST" action="{{ route('notulen.meetings.reprocess', $meeting) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">Proses ulang</button>
                            </form>
                        @endcan
                    @elseif (blank($meeting->full_text))
                        <p class="text-muted">Tidak ada teks yang diekstrak.</p>
                    @else
                        <pre class="bg-light p-3 rounded small" style="max-height: 480px; overflow-y: auto; white-space: pre-wrap;">{{ $meeting->full_text }}</pre>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('notulen.meetings.index') }}" class="btn btn-secondary btn-sm">Back to list</a>
                </div>
            </div>
        </div>
    </div>
@endsection
