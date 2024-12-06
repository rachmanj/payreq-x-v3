<?php

namespace App\Http\Controllers\Cashier;

use App\Exports\BilyetTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\BilyetController as ReportsBilyetController;
use App\Http\Controllers\UserController;
use App\Models\Bilyet;
use App\Models\BilyetTemp;
use App\Models\Giro;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BilyetController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page', 'dashboard');
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['admin', 'superadmin'], $userRoles)) {
            $giros = Giro::all();
        } else {
            $giros = Giro::where('project', auth()->user()->project)->get();
        }

        $views = [
            'dashboard' => 'cashier.bilyets.dashboard',
            'onhand' => 'cashier.bilyets.onhand',
            'release' => 'cashier.bilyets.release',
            'cair' => 'cashier.bilyets.cair',
            'void' => 'cashier.bilyets.void',
            'upload' => 'cashier.bilyets.upload',
        ];

        if ($page === 'dashboard') {
            $data = app(ReportsBilyetController::class)->dashboardData();
            return view($views[$page], compact('data'));
        } elseif ($page === 'onhand') {
            $onhands = Bilyet::where('status', 'onhand')->orderBy('prefix', 'asc')->orderBy('nomor', 'asc')->get();
            return view($views[$page], compact('giros', 'onhands'));
        } elseif ($page === 'upload') {
            // count giro_id that is null
            $giro_id_null = BilyetTemp::where('giro_id', null)->where('created_by', auth()->user()->id)->count();

            // cek data exist atau ngga
            $exist = BilyetTemp::where('created_by', auth()->user()->id)->exists();

            // cek duplikasi dan duplikasi tabel tujuan
            $duplikasi = app(BilyetTempController::class)->cekDuplikasi();
            $duplikasi_bilyet = app(BilyetTempController::class)->cekDuplikasiTabelTujuan();

            // jika ada giro_id yang null atau duplikasi, disable button import
            $import_button = !$exist || $giro_id_null > 0 || !empty($duplikasi) || !empty($duplikasi_bilyet) ? 'disabled' : null;
            $empty_button = $exist ? null : 'disabled';

            return view($views[$page], compact('giros', 'import_button', 'empty_button', 'duplikasi', 'duplikasi_bilyet'));
        } else {
            return view($views[$page], compact('giros'));
        }
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

        return redirect()->back()->with('success', 'Bilyet created successfully.');
    }

    public function update(Request $request, $id)
    {
        $bilyet = Bilyet::find($id);

        if ($request->is_void) {
            $bilyet->update([
                'bilyet_date' => $request->bilyet_date,
                'cair_date' => $request->cair_date,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
                'status' => 'void',
            ]);
        } else {
            if ($request->amount && $request->bilyet_date && $request->cair_date) {
                $status = 'cair';
            } elseif ($request->amount || $request->bilyet_date) {
                $status = 'release';
            } else {
                $status = 'onhand';
            }

            $bilyet->update([
                'bilyet_date' => $request->bilyet_date,
                'cair_date' => $request->cair_date,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
                'status' => $status,
            ]);
        }

        return redirect()->back()->with('success', 'Bilyet updated successfully.');
    }

    public function export()
    {
        return Excel::download(new BilyetTemplateExport, 'bilyet_template.xlsx');
    }

    public function destroy($id)
    {
        Bilyet::destroy($id);

        return redirect()->back()->with('success', 'Bilyet deleted successfully.');
    }

    public function import(Request $request)
    {
        // get all data from bilyet_temp
        $bilyets = BilyetTemp::where('created_by', auth()->user()->id)->get();
        $receive_date = $request->receive_date;

        // insert data to bilyet table
        foreach ($bilyets as $bilyet) {
            // $status = $bilyet->amount || $bilyet->bilyet_date || $bilyet->cair_date ? 'release' : 'onhand';

            if ($bilyet->amount && $bilyet->bilyet_date && $bilyet->cair_date) {
                $status = 'cair';
            } else {
                $status = $bilyet->amount || $bilyet->bilyet_date ? 'release' : 'onhand';
            }

            Bilyet::create([
                'giro_id' => $bilyet->giro_id,
                'prefix' => $bilyet->prefix,
                'nomor' => $bilyet->nomor,
                'type' => $bilyet->type,
                'receive_date' => $receive_date,
                'bilyet_date' => $bilyet->bilyet_date,
                'cair_date' => $bilyet->cair_date,
                'amount' => $bilyet->amount,
                'remarks' => $bilyet->remarks,
                'loan_id' => $bilyet->loan_id,
                'status' => $status,
                'created_by' => $bilyet->created_by,
                'project' => $bilyet->project,
            ]);
        }

        // delete all data from bilyet_temp
        BilyetTemp::where('created_by', auth()->user()->id)->delete();

        // return to the index page with success message
        return redirect()->back()->with('success', 'Bilyet imported successfully.');
    }

    public function update_many(Request $request)
    {
        // return $request->all();

        $bilyets = Bilyet::whereIn('id', $request->bilyet_ids)->get();

        foreach ($bilyets as $bilyet) {
            $bilyet->update([
                'bilyet_date' => $request->bilyet_date,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
                'status' => 'release',
            ]);
        }

        return redirect()->route('cashier.bilyets.index')->with('success', 'Bilyet updated successfully.');
    }

    public function data()
    {
        $status = request()->query('status');

        $userRoles = app(UserController::class)->getUserRoles();

        // determine which action button to show
        switch ($status) {
            case 'release':
                $bilyet_bystatus = Bilyet::where('status', 'release')->orderBy('bilyet_date', 'asc');
                $action_button = 'cashier.bilyets.release_action';
                break;
            case 'cair':
                $bilyet_bystatus = Bilyet::where('status', 'cair')->orderBy('cair_date', 'desc');
                $action_button = 'cashier.bilyets.cair_action';
                break;
            case 'trash':
                $bilyet_bystatus = Bilyet::where('status', 'void')->orderBy('updated_at', 'desc');
                $action_button = 'cashier.bilyets.void_action';
                break;
            default:
                $bilyet_bystatus = Bilyet::where('status', 'onhand');
                $action_button = 'cashier.bilyets.action';
                break;
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
                $remarks = $bilyet->remarks ? $bilyet->remarks : '';
                return '<small>' . $bilyet->giro->bank->name . ' ' . strtoupper($bilyet->giro->curr) . ' | ' . $bilyet->giro->acc_no . '<br>' . $remarks . '</small>';
            })
            ->editColumn('bilyet_date', function ($bilyet) {
                return $bilyet->bilyet_date ? date('d-M-Y', strtotime($bilyet->bilyet_date)) : '-';
            })
            ->editColumn('cair_date', function ($bilyet) {
                return $bilyet->cair_date ? date('d-M-Y', strtotime($bilyet->cair_date)) : '-';
            })
            ->editColumn('amount', function ($bilyet) {
                return $bilyet->amount ? number_format($bilyet->amount, 0, ',', '.') . ',-' : '-';
            })
            ->editColumn('type', function ($bilyet) {
                return strtoupper($bilyet->type);
            })
            ->addIndexColumn()
            ->addColumn('action', $action_button)
            ->rawColumns(['action', 'account', 'nomor'])
            ->toJson();
    }
}
