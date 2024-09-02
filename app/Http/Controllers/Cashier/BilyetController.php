<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Bilyet;
use App\Models\Giro;
use Illuminate\Http\Request;

class BilyetController extends Controller
{
    public function index()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['admin', 'superadmin'], $userRoles)) {
            $giros = Giro::all();
        } else {
            $giros = Giro::where('project', auth()->user()->project)->get();
        }

        return view('cashier.bilyets.index', compact('giros'));
    }

    public function data()
    {
        $bilyets = Bilyet::all();

        return datatables()->of($bilyets)
            ->addIndexColumn()
            ->addColumn('action', 'accounting.giros.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
