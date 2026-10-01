@extends('templates.main')

@section('title_page')
    Audit Login
@endsection

@section('breadcrumb_title')
    audit-login
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">100 Percobaan Login Terakhir</h3>
                </div>
                <div class="card-body">
                    <form method="get" action="{{ route('admin.login-audits.index') }}" class="form-inline mb-3">
                        <label for="q" class="sr-only">Cari username</label>
                        <input type="text" name="q" id="q" value="{{ $search }}"
                            class="form-control form-control-sm mr-2" placeholder="Cari username…">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        @if ($search !== '')
                            <a href="{{ route('admin.login-audits.index') }}" class="btn btn-sm btn-secondary ml-2">Reset</a>
                        @endif
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($audits as $audit)
                                    <tr>
                                        <td>{{ $audit->created_at?->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $audit->username_dicoba }}</td>
                                        <td>
                                            @if ($audit->berhasil)
                                                <span class="badge badge-success">Berhasil</span>
                                            @else
                                                <span class="badge badge-danger">Gagal</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $audit->ip }}</code></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada catatan login.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
