@extends('templates.main')

@section('title_page')
    Migrasi Payreqs
@endsection

@section('breadcrumb_title')
    migrasi / rabs
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Migrasi RABs</h3>
            {{-- create payreq --}}
            <div class="card-tools">
              <a href="{{ route('cashier.migrasi.index') }}" class="btn btn-primary btn-sm float-right">
                  <i class="fas fa-arrow-left"></i> Back
              </a>
            </div>
          </div>

        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <a href="{{ route('cashier.migrasi.rab.migrasi_rab') }}" class="btn btn-sm btn-warning">Migrate RAB</a>
              <a href="{{ route('cashier.migrasi.rab.realisasi_rab') }}" class="btn btn-sm btn-warning mx-5">Migrate Realisasi RAB</a>
            </div>
          </div>
        </div>
    </div>

  </div>
</div>
@endsection

@section('styles')
    <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}"/>
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
@endsection