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

    public function release()
    {
        return view('cashier.bilyets.release');
    }

    public function store(Request $request)
    {
        $request->validate([
            'prefix' => 'required',
            'nomor' => 'required',
            'giro_id' => 'required',
        ]);

        $request->merge([
            'status' => $request->amount || $request->bilyet_date || $request->cair_date ? 'release' : 'onhand'
        ]);

        Bilyet::create($request->all());

        return redirect()->route('cashier.bilyets.index')->with('success', 'Bilyet created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'bilyet_date' => 'required',
        ]);

        Bilyet::find($id)->update($request->all());

        return redirect()->route('cashier.bilyets.index')->with('success', 'Bilyet updated successfully.');
    }

    public function data()
    {
        $status = request()->query('status');

        $userRoles = app(UserController::class)->getUserRoles();

        if ($status == 'onhand') {
            $bilyet_bystatus = Bilyet::where('status', 'onhand');
        } else {
            $bilyet_bystatus = Bilyet::whereIn('status', ['release', 'cair', 'void']);
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $bilyets = $bilyet_bystatus->orderBy('project', 'asc')->get();
        } else {
            $bilyets = $bilyet_bystatus->where('project', auth()->user()->project)->get();
        }

        return datatables()->of($bilyets)
            ->editColumn('nomor', function ($bilyet) {
                return $bilyet->prefix . $bilyet->nomor;
            })
            ->addColumn('account', function ($bilyet) {
                return $bilyet->giro->bank->name . ' ' . strtoupper($bilyet->giro->curr) . ' | ' . $bilyet->giro->acc_no;
            })
            ->editColumn('bilyet_date', function ($bilyet) {
                return date('d-M-Y', strtotime($bilyet->bilyet_date));
            })
            ->editColumn('cair_date', function ($bilyet) {
                return $bilyet->cair_date ? date('d-M-Y', strtotime($bilyet->cair_date)) : '-';
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.bilyets.action')
            ->rawColumns(['action', 'account'])
            ->toJson();
    }
}
