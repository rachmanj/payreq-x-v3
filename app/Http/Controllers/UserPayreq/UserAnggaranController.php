<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use Illuminate\Http\Request;

class UserAnggaranController extends Controller
{
    public function index()
    {
        return view('user-payreqs.anggarans.index');
    }

    public function create()
    {
        return view('user-payreqs.anggarans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'amount' => 'required',
            'date' => 'required',
        ]);

        Anggaran::create([
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $request->date,
            'created_by' => auth()->user()->id,
        ]);

        return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil dibuat');
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array(['superadmin', 'admin'], $userRoles)) {
            $anggarans = Anggaran::orderBy('created_at', 'desc')
                ->get();
        } else {
            $anggarans = Anggaran::where('created_by', auth()->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($anggarans)
            ->addColumn('action', function ($anggaran) {
                return '<a href="' . route('user-payreqs.anggarans.show', $anggaran->id) . '" class="btn btn-primary btn-sm">Detail</a>';
            })
            ->addIndexColumn()
            // ->addColumn('action', 'cashier.modal.action')
            // ->rawColumns(['action', 'submitter', 'receiver'])
            ->toJson();
    }
}
