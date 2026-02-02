<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Creditor;
use App\Models\SapBusinessPartner;
use Illuminate\Http\Request;

class CreditorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.creditors.index');
    }

    /**
     * Get creditors data for DataTable
     */
    public function data()
    {
        $creditors = Creditor::with('sapBusinessPartner')->orderBy('name', 'asc')->get();

        return datatables()->of($creditors)
            ->addIndexColumn()
            ->addColumn('sap_code', function ($creditor) {
                if ($creditor->sapBusinessPartner) {
                    return '<span class="badge badge-success">' . $creditor->sapBusinessPartner->code . '</span>';
                }
                return '<span class="badge badge-warning">Not Linked</span>';
            })
            ->addColumn('sap_name', function ($creditor) {
                return $creditor->sapBusinessPartner?->name ?? '-';
            })
            ->addColumn('sap_status', function ($creditor) {
                if ($creditor->sapBusinessPartner) {
                    return $creditor->sapBusinessPartner->active
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-danger">Inactive</span>';
                }
                return '-';
            })
            ->addColumn('action', function ($creditor) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('admin.creditors.edit', $creditor->id) . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteCreditor(' . $creditor->id . ')"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['sap_code', 'sap_status', 'action'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sapPartners = SapBusinessPartner::suppliers()->active()->orderBy('name', 'asc')->get();
        return view('admin.creditors.create', compact('sapPartners'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:creditors,name',
            'sap_business_partner_id' => 'nullable|exists:sap_business_partners,id',
        ]);

        Creditor::create([
            'name' => $request->name,
            'sap_business_partner_id' => $request->sap_business_partner_id,
        ]);

        return redirect()->route('admin.creditors.index')
            ->with('success', 'Creditor created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Creditor $creditor)
    {
        $creditor->load('sapBusinessPartner');
        return view('admin.creditors.show', compact('creditor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Creditor $creditor)
    {
        $sapPartners = SapBusinessPartner::suppliers()->orderBy('name', 'asc')->get();
        $creditor->load('sapBusinessPartner');
        return view('admin.creditors.edit', compact('creditor', 'sapPartners'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Creditor $creditor)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:creditors,name,' . $creditor->id,
            'sap_business_partner_id' => 'nullable|exists:sap_business_partners,id',
        ]);

        $creditor->update([
            'name' => $request->name,
            'sap_business_partner_id' => $request->sap_business_partner_id,
        ]);

        return redirect()->route('admin.creditors.index')
            ->with('success', 'Creditor updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Creditor $creditor)
    {
        // Check if creditor is used in loans
        if ($creditor->loans()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete creditor that is linked to existing loans.',
            ], 422);
        }

        $creditor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Creditor deleted successfully.',
        ]);
    }
}
