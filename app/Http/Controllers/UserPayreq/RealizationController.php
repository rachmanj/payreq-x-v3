<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\Controller;
use App\Models\Realization;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;

class RealizationController extends Controller
{
    /**
     * Store a newly created realization detail.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeDetail(Request $request)
    {
        $request->validate([
            'realization_id' => 'required|exists:realizations,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'unit_no' => 'nullable|string|max:50',
            'nopol' => 'nullable|string|max:50',
            'qty' => 'nullable|numeric',
            'km_position' => 'nullable|numeric',
            'type' => 'nullable|string|max:50',
            'uom' => 'nullable|string|max:50',
        ]);

        // Create the realization detail
        $detail = RealizationDetail::create([
            'realization_id' => $request->realization_id,
            'description' => $request->description,
            'amount' => $request->amount,
            'unit_no' => $request->unit_no,
            'nopol' => $request->nopol,
            'qty' => $request->qty,
            'km_position' => $request->km_position,
            'type' => $request->type,
            'uom' => $request->uom,
        ]);

        // Check if it's an AJAX request
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Detail added successfully',
                'detail' => $detail
            ]);
        }

        // If it's a normal form submission, redirect with success message
        return redirect()->back()->with('success', 'Detail added successfully');
    }

    /**
     * Get all details for a realization.
     *
     * @param  \App\Models\Realization  $realization
     * @return \Illuminate\Http\Response
     */
    public function getDetails(Realization $realization)
    {
        $details = RealizationDetail::where('realization_id', $realization->id)->get();

        return response()->json([
            'success' => true,
            'details' => $details
        ]);
    }

    /**
     * Get a specific detail.
     *
     * @param  \App\Models\RealizationDetail  $detail
     * @return \Illuminate\Http\Response
     */
    public function getDetail(RealizationDetail $detail)
    {
        return response()->json($detail);
    }

    /**
     * Update a specific detail.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RealizationDetail  $detail
     * @return \Illuminate\Http\Response
     */
    public function updateDetail(Request $request, RealizationDetail $detail)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'unit_no' => 'nullable|string|max:50',
            'nopol' => 'nullable|string|max:50',
            'qty' => 'nullable|numeric',
            'km_position' => 'nullable|numeric',
            'type' => 'nullable|string|max:50',
            'uom' => 'nullable|string|max:50',
        ]);

        // Update the detail
        $detail->update([
            'description' => $request->description,
            'amount' => $request->amount,
            'unit_no' => $request->unit_no,
            'nopol' => $request->nopol,
            'qty' => $request->qty,
            'km_position' => $request->km_position,
            'type' => $request->type,
            'uom' => $request->uom,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail updated successfully',
            'detail' => $detail
        ]);
    }

    /**
     * Delete a realization detail.
     *
     * @param  \App\Models\RealizationDetail  $detail
     * @return \Illuminate\Http\Response
     */
    public function deleteDetail(RealizationDetail $detail)
    {
        $detail->delete();

        // Check if it's an AJAX request
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Detail deleted successfully'
            ]);
        }

        // If it's a normal form submission, redirect with success message
        return redirect()->back()->with('success', 'Detail deleted successfully');
    }

    /**
     * Submit the realization.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function submitRealization(Request $request)
    {
        $request->validate([
            'realization_id' => 'required|exists:realizations,id',
        ]);

        $realization = Realization::findOrFail($request->realization_id);

        // Check if there are details
        if ($realization->realizationDetails->count() == 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot submit realization without details'
                ], 422);
            }
            return redirect()->back()->with('error', 'Cannot submit realization without details');
        }

        // Update realization status
        $realization->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Realization submitted successfully'
            ]);
        }

        return redirect()->route('user-payreqs.realizations.index')->with('success', 'Realization submitted successfully');
    }
}
