@extends('templates.main')

@section('title_page')
    My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs / histories
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Payment Request Info</h3>
                    <a href="{{ route('search.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            @include('search.payreq_info')
                        </div>
                    </div>
                </div>
                @if ($payreq->realization)
                    <div class="card-header">
                        <h3 class="card-title">Realization Info</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                @include('search.realization_info')
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <h3 class="card-title">Realization Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                @include('search.realization_details')
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection