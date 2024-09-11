<?php

namespace App\Http\Controllers\Cashier;

use App\Exports\BilyetTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Bilyet;
use App\Models\BilyetTemp;
use App\Models\Giro;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    public function release_index()
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

    public function release(Request $request, $id)
    {
        $request->validate([
            'bilyet_date' => 'required',
            'amount' => 'required',
        ]);

        Bilyet::find($id)->update($request->all());

        return redirect()->route('cashier.bilyets.index')->with('success', 'Bilyet updated successfully.');
    }

    public function update(Request $request, $id)
    {
        if ($request->bilyet_date == null && $request->cair_date == null && $request->amount == null) {
            $request->merge([
                'status' => 'onhand'
            ]);
        } else {
            $request->merge([
                'status' => 'release'
            ]);
        }

        Bilyet::find($id)->update($request->all());

        return redirect()->route('cashier.bilyets.index')->with('success', 'Bilyet updated successfully.');
    }

    public function export()
    {
        return Excel::download(new BilyetTemplateExport, 'bilyet_template.xlsx');
    }

    public function import()
    {
        // get all data from bilyet_temp
        $bilyets = BilyetTemp::where('created_by', auth()->user()->id)->get();

        // insert data to bilyet table
        foreach ($bilyets as $bilyet) {
            $status = $bilyet->amount || $bilyet->bilyet_date || $bilyet->cair_date ? 'release' : 'onhand';

            Bilyet::create([
                'giro_id' => $bilyet->giro_id,
                'prefix' => $bilyet->prefix,
                'nomor' => $bilyet->nomor,
                'type' => $bilyet->type,
                'bilyet_date' => $bilyet->bilyet_date,
                'cair_date' => $bilyet->cair_date,
                'amount' => $bilyet->amount,
                'remarks' => $bilyet->remarks,
                'status' => $status,
                'created_by' => $bilyet->created_by,
                'project' => $bilyet->project,
            ]);
        }

        // delete all data from bilyet_temp
        BilyetTemp::where('created_by', auth()->user()->id)->delete();

        // return to the index page with success message
        return redirect()->route('cashier.bilyet-temps.index')->with('success', 'Bilyet imported successfully.');
    }

    public function data()
    {
        $status = request()->query('status');

        $userRoles = app(UserController::class)->getUserRoles();

        if ($status == 'onhand') {
            $bilyet_bystatus = Bilyet::where('status', 'onhand');
            $action_button = 'cashier.bilyets.action';
        } else {
            $bilyet_bystatus = Bilyet::whereIn('status', ['release', 'cair', 'void']);
            $action_button = 'cashier.bilyets.release_action';
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $bilyets = $bilyet_bystatus->orderBy('project', 'asc')->get();
        } else {
            $bilyets = $bilyet_bystatus->where('project', auth()->user()->project)->get();
        }

        // $bilyets = Bilyet::all();

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
            ->addColumn('action', $action_button)
            ->rawColumns(['action', 'account'])
            ->toJson();
    }
}
