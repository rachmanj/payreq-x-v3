<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Wtax23;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Wtax23Controller extends Controller
{
    public function index()
    {
        $page = request('page');
        $status = request('status');

        $views = [
            'dashboard' => 'accounting.wtax23.dashboard',
            'purchase' => $status == 'outstanding' ? 'accounting.wtax23.ap.outstanding' : 'accounting.wtax23.ap.complete',
            'default' => $status == 'outstanding' ? 'accounting.wtax23.ar.outstanding' : 'accounting.wtax23.ar.complete'
        ];

        return view($views[$page] ?? $views['default']);
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
            $extension = $file->getClientOriginalExtension();
            $filename = 'wtax_' . uniqid() . '.' . $extension;
            $file->move(public_path('wtax'), $filename);
            $document->filename = $filename;
        }

        $document->save();

        return redirect()->back()->with('success', 'Bupot number and attachment updated successfully');
    }

    public function data()
    {
        $page = request()->query('page');
        $status = request()->query('status');

        $query = Wtax23::query();

        if ($page === 'purchase') {
            $query->where('doc_type', 'out');
            $action_button = $status === 'outstanding' ? 'accounting.wtax23.ap.action' : 'accounting.wtax23.ap.action_complete';
        } else {
            $query->where('doc_type', 'in');
            $action_button = $status === 'outstanding' ? 'accounting.wtax23.ar.action' : 'accounting.wtax23.ar.action_complete';
        }

        if ($status === 'outstanding') {
            $query->whereNull('bupot_no')->orderBy('posting_date', 'asc');
        } else {
            $query->whereNotNull('bupot_no')->orderBy('updated_at', 'desc');
        }

        $documents = $query->get();

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
                $bupotAt = Carbon::parse($document->bupot_at)->addHours(8)->format('d-M-Y H:i');
                return '<small>' . $document->bupot_by . '</small><br><small>at ' . $bupotAt . '</small>';
            })
            ->addColumn('doc_date', function ($document) {
                $createDate = Carbon::parse($document->create_date)->format('d-M-Y');
                $postingDate = Carbon::parse($document->posting_date)->format('d-M-Y');
                return $createDate . '<br>' . $postingDate;
            })
            ->addColumn('action', $action_button)
            ->addIndexColumn()
            ->rawColumns(['remarks', 'action', 'bupot_by', 'doc_date'])
            ->toJson();
    }
}
