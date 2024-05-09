@extends('templates.main')

@section('title_page')
  Migrasi
@endsection

@section('breadcrumb_title')
    migrasi
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card racd-info">
      <div class="card-header">
        <h3 class="card-title">Migrasi Proses</h3><br>
        <p style="color: blue;">This action is to Sync department_id on some tables</p>
      </div>
      <div class="card-body">
        {{-- <a href="#" type="submit" class="btn btn-info" onclick="return confirm('Are you sure you want to sync?')" style="width: 100%">Check data on tables</a> --}}
      </div>
      <div class="card-body">
        <table>
          <thead>
            <tr>
              <th style="width: 80%;">Table Name</th>
              <th style="width: 20%;">Data Exist</th>
            </tr>
          </thead>
          <tbody>
              @foreach ($check_tables as $item)
                <tr>
                <td>{{ $item['table_name'] }}</td>
                <td style="text-align: center; color: {{ $item['is_exist'] ? 'green' : 'red' }}">{{ $item['is_exist'] ? '✓' : 'x' }}</td>
                </tr>
              </tr>
              @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer text-center">
      </div>
    </div> 
  </div>
</div>

@endsection