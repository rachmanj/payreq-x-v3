@extends('templates.main')

@section('title_page')
    
@endsection

@section('breadcrumb_title')
    search
@endsection

@section('content')
<div class="container-fluid">
  <h2 class="text-center display-4">Search</h2>
  <div class="row">
      <div class="col-md-8 offset-md-2">
          <form action="{{ route('search.display') }}" method="POST">
            @csrf
              <div class="input-group">
                  <input type="search" name="document_no" class="form-control form-control-lg" placeholder="Type document number here" required >
                  <div class="input-group-append">
                      <button type="submit" class="btn btn-lg btn-default">
                          <i class="fa fa-search"></i>
                      </button>
                  </div>
              </div>
          </form>
      </div>
  </div>
</div>
@endsection