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
                'name' => 'Report 2',
                'subMenu' => [
                    [
                        'name' => 'Report 2.1',
                        'url' => 'report2.1',
                    ],
                    [
                        'name' => 'Report 2.2',
                        'url' => 'report2.2',
                    ],
                ],
            ],
        ];

        return $menuList;
    }
    // private 
}
