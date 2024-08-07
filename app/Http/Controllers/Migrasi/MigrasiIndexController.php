<?php

namespace App\Http\Controllers\Migrasi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MigrasiIndexController extends Controller
{
    public function index()
    {
        // return $this->menuList;

        return view('migrasi.index', [
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
                        'name' => 'Migrasi Payreq Belum Realisasi',
                        'url' => route('cashier.migrasi.payreqs.index'),
                        'protector' => null,
                    ],
                    [
                        'name' => 'Create Payreq Belum dibuat dana sudah paid',
                        'url' => route('cashier.migrasi.payreqs.index'),
                        'protector' => null,
                    ],
                ],
            ],
            [
                'name' => 'BUC / RAB',
                'protector' => null,
                'subMenu' => [
                    [
                        'name' => 'Migrasi RAB table ke Anggaran table',
                        'url' => route('cashier.migrasi.rab.index'),
                        'protector' => null,
                    ],
                    [
                        'name' => 'Migrasi rab_id to realisasi_anggarans',
                        'url' => route('cashier.migrasi.rab.index'),
                        'protector' => null,
                    ],
                ],
            ],
        ];

        return $menuList;
    }
}
