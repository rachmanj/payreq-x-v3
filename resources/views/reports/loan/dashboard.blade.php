@extends('templates.main')

@section('title_page')
  Loan Dashboard
@endsection

@section('breadcrumb_title')
    reports / loan / dashboard
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">Rekaps</h3>
      </div>
      <div class="card-body">
        <dl class="row">
          <dt class="col-sm-4">Total Outstanding Amount</dt>
          <dd class="col-sm-8">: IDR {{ $dashboard_data['outstanding_installment_amount'] }}</dd>
          <dt class="col-sm-4">Outstanding This Month</dt>
          <dd class="col-sm-8">: IDR {{ $dashboard_data['outstanding_installment_amount_this_month'] }}</dd>
          <dt class="col-sm-4">Paid This Month</dt>
          <dd class="col-sm-8">: IDR {{ $dashboard_data['paid_this_month'] }}</dd>
        </dl>
      </div>
    </div> 
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">Outstanding Loan by Creditors</h3>
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Creditor</th>
              <th class="text-right">Number of Loans</th> <!-- New column -->
              <th class="text-right">Outstanding Amount</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($dashboard_data['outstanding_installment_amount_by_creditors'] as $index => $creditor)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $creditor->creditor_name }}</td>
                <td class="text-right">{{ $creditor->number_of_loans }}</td> <!-- New column data -->
                <td class="text-right">IDR {{ number_format($creditor->total, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">Outstanding Installment by Loan</h3>
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Agreement</th>
              <th>Creditor</th> 
              <th>Desc</th> 
              <th class="text-right">Installment Left</th>
              <th class="text-right">Outstanding Amount</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($dashboard_data['outstanding_installment_amount_by_loan_code'] as $index => $loan)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $loan->loan_code }}</td>
                <td>{{ $loan->lessor_name }}</td>
                <td>{{ $loan->description }}</td>
                <td class="text-right">{{ $loan->number_of_installments_left }}</td>
                <td class="text-right">IDR {{ number_format($loan->total, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>



@endsection