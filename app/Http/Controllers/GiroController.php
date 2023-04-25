<?php

namespace App\Http\Controllers;

use App\Models\Giro;
use App\Models\GiroDetail;
use Illuminate\Http\Request;

class GiroController extends Controller
{
    public function index()
    {
        $banks = ['Bank Mandiri'];
        $accounts = ['1490004194751', '1490007118583'];

        return view('giros.index', compact('banks', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank' => 'required',
            'account' => 'required',
            'nomor' => 'required',
        ]);

        if ($request->file_upload) {
            $file = $request->file('file_upload');
            $filename = rand() . '_' . $file->getClientOriginalName();
            $file->move(public_path('document_upload'), $filename);
        } else {
            $filename = null;
        }

        if ($request->tanggal) {
            $tanggal = $request->tanggal;
        } else {
            $tanggal = date('Y-m-d');
        }

        $giro = Giro::create([
            'tanggal' => $tanggal,
            'nomor' => $request->nomor,
            'bank' => $request->bank,
            'account' => $request->account,
            'giro_type' => $request->giro_type,
            'remarks' => $request->remarks,
            'filename' => $filename,
        ]);

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Create Giro', $giro->nomor);

        return redirect()->route('giros.detail.index', $giro->id);
    }

    public function edit($id)
    {
        $giro = Giro::find($id);
        $banks = ['Bank Mandiri'];
        $accounts = ['1490004194751', '1490007118583'];

        return view('giros.edit', compact('giro', 'banks', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required',
            'bank' => 'required',
            'account' => 'required',
            'nomor' => 'required',
        ]);

        $giro = Giro::find($id);
        $giro->tanggal = $request->tanggal;
        $giro->nomor = $request->nomor;
        $giro->bank = $request->bank;
        $giro->account = $request->account;
        $giro->giro_type = $request->giro_type;
        $giro->remarks = $request->remarks;
        $giro->use_for = $request->use_for;

        if ($request->file_upload) {
            $file = $request->file('file_upload');
            $filename = rand() . '_' . $file->getClientOriginalName();
            $file->move(public_path('document_upload'), $filename);
            $giro->filename = $filename;
        }

        $giro->save();

        return redirect()->route('giros.index')->with('success', 'Data successfully updated');
    }

    public function destroy($id)
    {
        $giro = Giro::find($id);
        $giro->delete();

        return redirect()->route('giros.index')->with('success', 'Data successfully deleted');
    }

    public function data()
    {
        $giros = Giro::orderBy('tanggal', 'desc')->orderBy('account', 'asc')->get();

        return datatables()->of($giros)
            ->editColumn('tanggal', function ($giros) {
                return date('d-m-Y', strtotime($giros->tanggal));
            })
            ->editColumn('bank', function ($giros) {
                return $giros->bank . ' | ' . $giros->account;
            })
            ->addColumn('amount', function ($giros) {
                $amount = GiroDetail::where('giro_id', $giros->id)->sum('amount');
                return number_format($amount, 0);
            })
            ->addIndexColumn()
            ->addColumn('action', 'giros.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
