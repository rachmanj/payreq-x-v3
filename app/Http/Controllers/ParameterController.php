<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function index()
    {
        return view('parameters.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name1' => 'required',
            'name2' => 'required',
            'param_value' => 'required',
        ]);

        Parameter::create($validated);

        return redirect()->route('parameters.index')->with('success', 'Parameter created successfully.');
    }

    public function update(Request $request, Parameter $parameter)
    {
        $validated = $request->validate([
            'name1' => 'required',
            'name2' => 'required',
            'param_value' => 'required',
        ]);

        $parameter->update($validated);

        return redirect()->route('parameters.index')->with('success', 'Parameter updated successfully.');
    }

    public function data()
    {
        $parameters = Parameter::orderBy('name1', 'asc')->get();

        return datatables()->of($parameters)
            ->editColumn('updated_at', function ($parameter) {
                return $parameter->updated_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addIndexColumn()
            ->addColumn('action', 'parameters.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
