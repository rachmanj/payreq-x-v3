<?php

namespace App\Http\Controllers;

use App\Models\AdvanceCategory;
use Illuminate\Http\Request;

class AdvanceCategoryController extends Controller
{
    public function index()
    {
        return view('adv-category.index');
    }

    public function store(Request $request)
    {
        AdvanceCategory::create($request->all());
        return redirect()->route('adv-category.index')->with('success', 'Advance Category created successfully.');
    }

    public function update(Request $request, $id)
    {
        $category = AdvanceCategory::findOrFail($id);
        $category->update($request->all());
        return redirect()->route('adv-category.index')->with('success', 'Advance Category updated successfully.');
    }

    public function data()
    {
        $adv_categories = AdvanceCategory::orderBy('code', 'asc')->get();

        return datatables()->of($adv_categories)
            ->addIndexColumn()
            ->addColumn('action', 'adv-category.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
