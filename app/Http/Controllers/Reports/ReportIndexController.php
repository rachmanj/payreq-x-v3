<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;


class ReportIndexController extends Controller
{
    public function index()
    {
        // return $this->menuList;

        return view('reports.index', [
            'menuList' => $this->menuList(),
        ]);
    }

    public function menuList()
    {
        $menuList = [
            [
                'name' => 'Payment Request',
                'protector' => null,
                'subMenu' => [
                    // [
                    //     'name' => 'Ongoing Payment Request',
                    //     'url' => route('reports.ongoing.index'),
                    // ],
                    [
                        'name' => 'Dashboard 000H',
                        'url' => route('reports.ongoing.dashboard', ['project' => '000H']),
                        'protector' => 'akses_dashboard_000H',
                    ],
                    [
                        'name' => 'Dashboard 001H',
                        'url' => route('reports.ongoing.dashboard', ['project' => '001H']),
                        'protector' => 'akses_dashboard_001H',
                    ],
                    [
                        'name' => 'Dashboard 017C',
                        'url' => route('reports.ongoing.dashboard', ['project' => '017C']),
                        'protector' => 'akses_dashboard_017C',
                    ],
                    [
                        'name' => 'Dashboard 021C',
                        'url' => route('reports.ongoing.dashboard', ['project' => '021C']),
                        'protector' => 'akses_dashboard_021C',
                    ],
                    [
                        'name' => 'Dashboard 022C',
                        'url' => route('reports.ongoing.dashboard', ['project' => '022C']),
                        'protector' => 'akses_dashboard_022C',
                    ],
                    [
                        'name' => 'Dashboard 023C',
                        'url' => route('reports.ongoing.dashboard', ['project' => '023C']),
                        'protector' => 'akses_dashboard_023C',
                    ],
                    [
                        'name' => 'Payreq Aging',
                        'url' => route('reports.ongoing.payreq-aging.index'),
                        'protector' => 'akses_payreq_aging',
                    ],
                    [
                        'name' => 'EOM',
                        'url' => route('reports.eom.index'),
                        'protector' => 'akses_eom',
                    ]
                ],
            ],
            [
                'name' => 'Cashier Related',
                'protector' => null,
                'subMenu' => [
                    [
                        'name' => 'Today Transaction',
                        'url' => route('reports.cashier.index'),
                        'protector' => 'akses_today_transaction',
                    ],
                    [
                        'name' => 'Report 2.2',
                        'url' => 'report2.2',
                        'protector' => null,
                    ],
                ],
            ],
            [
                'name' => 'Equipment Related',
                'protector' => null,
                'subMenu' => [
                    [
                        'name' => 'Sum Expense by Equipment',
                        'url' => route('reports.equipment.index'),
                        'protector' => 'akses_sum_expense_by_equipment',
                    ],
                    [
                        'name' => 'Report 2.2',
                        'url' => 'report2.2',
                        'protector' => null,
                    ],
                ],
            ],
            [
                'name' => 'Loan Related',
                'protector' => 'akses_loan_report',
                'subMenu' => [
                    [
                        'name' => 'BG Jatuh Tempo dalam waktu dekat',
                        'url' => route('reports.loan.index'),
                        'protector' => null,
                    ],
                    [
                        'name' => 'Loan Dashboard',
                        'url' => route('reports.loan.dashboard'),
                        'protector' => null,
                    ],
                ],
            ],
            [
                'name' => 'RAB Related',
                'protector' => null,
                'subMenu' => [
                    [
                        'name' => 'Periode RAB',
                        'url' => route('reports.periode-anggaran.index'),
                        'protector' => 'akses_periode_anggaran',
                    ],
                    [
                        'name' => 'RAB List',
                        'url' => route('reports.anggaran.index'),
                        'protector' => 'akses_report_rab',
                    ],
                ],
            ],
        ];

        return $menuList;
    }
    // private 
}
