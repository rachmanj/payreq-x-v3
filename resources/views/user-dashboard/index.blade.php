@extends('templates.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@section('content')
    <div class="content">
      <div class="container-fluid">

        <div class="row">
          @include('user-dashboard.row1')
        </div>

        <hr>

        <div class="row">
          <div class="col-12">
            @include('user-dashboard.just_approved')
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            @include('user-dashboard.not_realization')
          </div>
        </div>

          <div class="row">
            <div class="col-12">
              @include('user-dashboard.not_verify')
            </div>
          </div>

      </div>
    </div>
@endsection