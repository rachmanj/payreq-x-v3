<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Wtax23;
use Illuminate\Http\Request;

class Wtax23Controller extends Controller
{
    public function index()
    {
        $status = request('status');
        if ($status == 'paid') {
            return view('accounting.wtax23.paid');
        } else {
            return view('accounting.wtax23.index');
        }
    }

    public function update(Request $request, $id)
    {
        $existingDocument = Wtax23::where('bupot_no', $request->input('bupot_no'))->first();
        if ($existingDocument && $existingDocument->id != $id) {
            return redirect()->back()->with('error', 'Bupot number already exists.');
        }

        $document = Wtax23::findOrFail($id);
        $document->bupot_no = $request->input('bupot_no');
        $document->bupot_date = $request->input('bupot_date');
        $document->bupot_by = auth()->user()->name;
        $document->bupot_at = now();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'wtax_' . uniqid() . $file->getClientOriginalName();
            $file->move(public_path('wtax'), $filename);
            $document->filename = $filename;
        }

        $document->save();

        return redirect()->back()->with('success', 'Bupot number and attachment updated successfully');
    }

    public function data()
    {
        $status = request('status');
        $action_button = $status == 'paid' ? 'accounting.wtax23.action_paid' : 'accounting.wtax23.action';

        if ($status == 'paid') {
            $documents = Wtax23::whereNotNull('bupot_no')
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            $documents = Wtax23::whereNull('bupot_no')
                ->orderBy('posting_date', 'asc')
                ->get();
        }

        return datatables()->of($documents)
            ->editColumn('amount', function ($document) {
                return number_format($document->amount, 2);
            })
            ->editColumn('create_date', function ($document) {
                return date('d-M-Y', strtotime($document->create_date));
            })
            ->editColumn('posting_date', function ($document) {
                return date('d-M-Y', strtotime($document->posting_date));
            })
            ->editColumn('remarks', function ($document) {
                return '<small>' . strtolower($document->remarks) . '</small>';
            })
            // add column name days that count the difference between posting_date and today
            ->editColumn('days', function ($document) {
                $today = date('Y-m-d');
                $diff = date_diff(date_create($document->posting_date), date_create($today));
                return $diff->format('%a');
            })
            ->editColumn('bupot_by', function ($document) {
                $bupotAt = date('d-M-Y H:i', strtotime($document->bupot_at . ' +8 hours'));
                return '<small>' . $document->bupot_by . '</small><br><small>at ' . $bupotAt . '</small>';
            })
            ->addColumn('action', $action_button)
            ->addIndexColumn()
            ->rawColumns(['remarks', 'action', 'bupot_by'])
            ->toJson();
    }
}
