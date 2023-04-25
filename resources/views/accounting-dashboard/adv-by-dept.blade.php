
        <div class="card card-info">
            <div class="card-header border-transparent">
                <h3 class="card-title"><b>Monthly Advance by Department</b></h3>
                <h3 class="card-title float-right">(IDR 000)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table m-0 table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Dept</th>
                            @foreach ($department_months as $month)
                                <th class="text-center">{{ date('M', strtotime('2022-' . $month->month . '-01')) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                       @foreach ($payreq_departments as $department)
                           <tr>
                                <th>{{ $department->akronim }}</th>
                                    @foreach ($months as $month)
                                        <td class="text-right">{{ number_format($byDepartments->where('month', $month->month)->where('department.akronim', $department->akronim)->sum('payreq_idr') / 1000, 0) }}</td>
                                    @endforeach
                           </tr>
                       @endforeach
                    </tbody>
                </table>
            </div>
        </div>

