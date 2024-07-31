<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PeriodeAnggaran;
use App\Models\Project;
use Illuminate\Http\Request;

class PeriodeAnggaranController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('code', 'asc')->get();

        return view('reports.periode-anggaran.index', compact('projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode' => 'required',
            'project' => 'required',
        ]);

        PeriodeAnggaran::create([
            'periode' => $request->periode . '-01',
            'project' => $request->project,
            'periode_type' => $request->periode_type,
            'is_active' => $request->is_active == 'yes' ? 1 : 0,
            'description' => $request->description,
        ]);

        return redirect()->route('reports.periode-anggaran.index')->with('success', 'Periode anggaran berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'periode' => 'required',
            'project' => 'required',
        ]);

        $periode = PeriodeAnggaran::find($id);
        $periode->update([
            'periode' => $request->periode . '-01',
            'project' => $request->project,
            'periode_type' => $request->periode_type,
            'is_active' => $request->is_active == 'yes' ? 1 : 0,
            'description' => $request->description,
        ]);

        return redirect()->route('reports.periode-anggaran.index')->with('success', 'Periode anggaran berhasil diubah');
    }

    public function data()
    {
        $parameters = PeriodeAnggaran::orderBy('periode', 'desc')->get();

        return datatables()->of($parameters)
            ->editColumn('periode', function ($parameter) {
                return date('F Y', strtotime($parameter->periode));
            })
            ->editColumn('is_active', function ($parameter) {
                $active = '<span class="badge badge-success">Active</span>';
                $inactive = '<span class="badge badge-danger">Inactive</span>';
                return $parameter->is_active == 1 ? $active : $inactive;
            })
            ->editColumn('description', function ($parameter) {
                return '<small>' . $parameter->description . '</small>';
            })
            ->editColumn('periode_type', function ($parameter) {
                return $parameter->periode_type == 'anggaran' ? 'Anggaran' : 'OFR';
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.periode-anggaran.action')
            ->rawColumns(['action', 'is_active', 'description'])
            ->toJson();
    }
}
