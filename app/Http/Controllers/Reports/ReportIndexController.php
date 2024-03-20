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
                'subMenu' => [
                    [
                        'name' => 'Ongoing Payment Request',
                        'url' => route('reports.ongoing.index'),
                    ],
                    [
                        'name' => 'Report 1.2',
                        'url' => 'report1.2',
                    ],
                ],
            ],
            [
                'name' => 'Equipment Related',
                'subMenu' => [
                    [
                        'name' => 'Sum Expense by Equipment',
                        'url' => route('reports.equipment.index'),
                    ],
                    [
                        'name' => 'Report 2.2',
                        'url' => 'report2.2',
                    ],
                ],
            ],
            [
                'name' => 'Loan Related',
                'subMenu' => [
                    [
                        'name' => 'BG Jatuh Tempo dalam waktu dekat',
                        'url' => route('reports.loan.index'),
                    ],
                    [
                        'name' => 'Loan Dashboard',
                        'url' => route('reports.loan.dashboard'),
                    ],
                ],
            ],
        ];

        return $menuList;
    }
    // private 
}
