@extends('templates.main')

@section('title_page')
    Reports
@endsection

@section('breadcrumb_title')
    reports
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-folder"></i>
            Report Index
          </h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <ol>
            @foreach ($menuList as $item)
                @if ($item['protector'] && $item['protector'] === 'akses_loan_report')
                  @can('akses_loan_report')
                  <li>{{ $item['name'] }}</li>
                    <ol>
                      @foreach ($item['subMenu'] as $subItem)
                        <li><a href="{{ $subItem['url'] }}">{{ $subItem['name'] }}</a></li>
                      @endforeach
                    </ol>
                  @endcan
                @else
                <li>{{ $item['name'] }}</li>
                  <ol>
                    @foreach ($item['subMenu'] as $subItem)
                      <li><a href="{{ $subItem['url'] }}">{{ $subItem['name'] }}</a></li>
                    @endforeach
                  </ol>
                @endif
            @endforeach
          </ol>
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
    
    
  </div>
  <!-- /.col -->
</div>
<!-- /.row -->

@endsection
