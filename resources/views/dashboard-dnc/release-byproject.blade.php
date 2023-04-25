<div class="card card-info">
    <div class="card-header border-transparent">
        <h3 class="card-title"><b>Outgoings By Project </b><small>(IDR 000)</small></h3>
    </div>
    <div class="card-body p-0">
        <table class="table m-0 table-striped table-bordered">
            <thead>
                <tr>
                    <th>Project</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rab_projects as $project)
                    <tr>
                        <td>{{ $project->project_code }}</td>
                        <td class="text-right">{{ number_format($release_amount_by_project->where('project_code', $project->project_code)->sum('payreqs_sum_payreq_idr') / 1000, 0) }}</td>
                    </tr>
                @endforeach
                <th>Total</th>
                <th class="text-right">{{ number_format($release_amount_by_project->sum('payreqs_sum_payreq_idr') / 1000, 0) }}</th>
            </tbody>
        </table>
    </div>
</div>