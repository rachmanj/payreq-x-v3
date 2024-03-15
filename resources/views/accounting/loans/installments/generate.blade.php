@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans  / installment / generate
@endsection

@section('content')
<div class="row">
    <div class="col-12">

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Generate Installment for Loan {{ $loan->loan_code }}</h3>
          <a href="{{ route('accounting.loans.show', $loan->id) }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
          <form action="{{ route('accounting.loans.installments.store_generate') }}" method="POST">
            @csrf

            <input type="hidden" name="loan_id" value="{{ $loan->id }}">
            <div class="row">
              <div class="col-4">
                <div class="form-group">
                  <label for="start_due_date">Tanggal Mulai</label>
                  <input type="date" name="start_due_date" class="form-control" value="{{ old('start_due_date') }}" >
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="tenor">Tenor</label>
                  <input type="number" name="tenor" value="{{ old('tenor', $loan->tenor) }}" class="form-control" >
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="installment_amount">Amount per bulan</label>
                  <input type="text" name="installment_amount" value="{{ old('installment_amount')}}" class="form-control">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-4">
                <div class="form-group">
                  <label for="start_angsuran_ke">Mulai Angsuran ke </small></label>
                  <input type="number" name="start_angsuran_ke" class="form-control" value="{{ old('start_angsuran_ke') }}" >
                </div>
              </div>
              <div class="col-4">
                <label for="account_id">Account Penbebanan</small></label>
                <select name="account_id" class="form-control select2bs4 @error('creditor_id') is-invalid @enderror">
                  <option value="">-- select account --</option>
                  @foreach ($accounts as $account)
                  <option value="{{ $account->id }}" {{ $account->id == old('account_id') ? "selected" : "" }}>{{ $account->account_number }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-4">
                
              </div>
            </div>
            
            <div class="card-footer">
              <div class="row">
                <div class="col-12">
                  <button type="submit" class="btn btn-success btn-sm btn-block"><i class="fas fa-save"></i> Generate</button>
                </div>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
@endsection