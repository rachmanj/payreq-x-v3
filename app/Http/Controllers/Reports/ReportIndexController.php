<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;


class ReportIndexController extends Controller
{
    public function index()
    {
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
                        'name' => 'EOM',
                        'url' => route('reports.eom.index'),
                        'protector' => 'akses_eom',
                    ],
                    [
                        'name' => 'Rekap Advance HO',
                        'url' => route('reports.cashier.rekap-advance.index', ['project' => '000H']),
                        'protector' => 'see_rekap_advance_ho',
                    ],
                    [
                        'name' => 'Rekap Advance BO',
                        'url' => route('reports.cashier.rekap-advance.index', ['project' => '001H']),
                        'protector' => 'see_rekap_advance_bo',
                    ],
                    [
                        'name' => 'Rekap Advance 017C',
                        'url' => route('reports.cashier.rekap-advance.index', ['project' => '017C']),
                        'protector' => 'see_rekap_advance_017',
                    ],
                    [
                        'name' => 'Rekap Advance 021C',
                        'url' => route('reports.cashier.rekap-advance.index', ['project' => '021C']),
                        'protector' => 'see_rekap_advance_021',
                    ],
                    [
                        'name' => 'Rekap Advance 022C',
                        'url' => route('reports.cashier.rekap-advance.index', ['project' => '022C']),
                        'protector' => 'see_rekap_advance_022',
                    ],
                    [
                        'name' => 'Rekap Advance 023C',
                        'url' => route('reports.cashier.rekap-advance.index', ['project' => '023C']),
                        'protector' => 'see_rekap_advance_023',
                    ],
                ],
            ],
            [
                'name' => 'Dokumen Related',
                'protector' => null,
                'subMenu' => [
                    [
                        'name' => 'Rekap Rekening Koran',
                        'url' => route('reports.dokumen.index', ['type' => 'koran', 'year' => date('Y')]),
                        'protector' => 'report_dokumen_koran',
                    ],
                    [
                        'name' => 'Rekap PCBC',
                        'url' => route('reports.dokumen.index', ['type' => 'pcbc']),
                        'protector' => 'report_dokumen_pcbc',
                    ],
                ],
            ],
            [
                'name' => 'Loan / Bilyet Related',
                'protector' => null,
                'subMenu' => [
                    [
                        'name' => 'Bilyet Dashboard',
                        'url' => route('reports.bilyet.index'),
                        'protector' => 'see_bilyet_dashboard',
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
            [
                'name' => 'Equipment Related',
                'protector' => null,
                'subMenu' => [
                    [
                        'name' => 'Sum Expense by Equipment',
                        'url' => route('reports.equipment.index'),
                        'protector' => 'akses_sum_expense_by_equipment',
                    ],
                ],
            ],
        ];

        return $menuList;
    }
    // private 
}
