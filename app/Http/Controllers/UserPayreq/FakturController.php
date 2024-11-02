<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Customer;
use App\Models\Faktur;
use Illuminate\Http\Request;

class FakturController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('name', 'asc')->get();

        return view('user-payreqs.fakturs.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required',
            'invoice_no' => 'required',
            'invoice_date' => 'required',
            'dpp' => 'required',
        ]);

        $validatedData['remarks'] = $request->remarks;
        $validatedData['created_by'] = auth()->user()->id;
        $validatedData['submit_at'] = now();

        Faktur::create($validatedData);

        return redirect()->route('user-payreqs.fakturs.index')->with('success', 'Faktur created successfully.');
    }

    public function update_arinvoice(Request $request, $id)
    {
        $faktur = Faktur::findOrFail($id);

        $validatedData = $request->validate([
            'customer_id' => 'required',
            'invoice_no' => 'required',
            'invoice_date' => 'required',
            'dpp' => 'required',
        ]);

        $validatedData['remarks'] = $request->remarks;

        $faktur->update($validatedData);

        return redirect()->route('user-payreqs.fakturs.index')->with('success', 'Faktur updated successfully.');
    }

    public function update_faktur(Request $request)
    {
        // update faktur, field to update are faktur_no, faktur_date, ppn and upload attachment
        $faktur = Faktur::findOrFail($request->faktur_id);

        $validatedData = $request->validate([
            'faktur_no' => 'required',
            'faktur_date' => 'required',
            'ppn' => 'required',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $validatedData['response_by'] = auth()->user()->id;
        $validatedData['updated_at'] = now();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'faktur_' . rand() . '_' . $file->getClientOriginalName();
            $file->move(public_path('file_upload'), $filename);
            $validatedData['attachment'] = $filename;
        }

        $faktur->update($validatedData);

        return redirect()->route('user-payreqs.fakturs.index')->with('success', 'Faktur updated successfully.');
    }

    public function destroy($id)
    {
        $faktur = Faktur::findOrFail($id);
        $faktur->delete();

        return redirect()->route('user-payreqs.fakturs.index')->with('success', 'Faktur deleted successfully.');
    }

    public function data()
    {
        $getUserRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['admin', 'superadmin'], $getUserRoles)) {
            $fakturs = Faktur::orderBy('invoice_date', 'desc')->get();
        } else {
            $fakturs = Faktur::orderBy('invoice_date', 'desc')
                ->get();
        }

        return datatables()->of($fakturs)
            ->addColumn('customer', function ($faktur) {
                return '<small>' . $faktur->customer_name . '</small>';
            })
            ->editColumn('remarks', function ($faktur) {
                return '<small>' . $faktur->remarks . '</small></br><small>kurs: ' . $faktur->kurs . '</small>';
            })
            ->editColumn('amount', function ($faktur) {
                return '<small>DPP: ' . number_format($faktur->dpp, 2) . '</small><br><small>PPN: ' . number_format($faktur->ppn, 2) . '</small>';
            })
            ->addColumn('invoice_info', function ($faktur) {
                $invoice_date = $faktur->invoice_date ? \Carbon\Carbon::parse($faktur->invoice_date)->format('d-M-Y') : null;
                return '<small>No. ' . $faktur->invoice_no . '</small><br><small>Date: ' . $invoice_date . '</small>';
            })
            ->addColumn('faktur_info', function ($faktur) {
                $faktur_date = $faktur->faktur_date ? \Carbon\Carbon::parse($faktur->faktur_date)->format('d-M-Y') : null;
                return '<small>No. ' . $faktur->faktur_no . '</small><br><small>Date: ' . $faktur_date . '</small>';
            })
            ->addColumn('users', function ($faktur) {
                $createdByFirstName = explode(' ', $faktur->created_by_name)[0];
                $responseByFirstName = explode(' ', $faktur->response_by_name)[0];
                return '<small>Request by: ' . $createdByFirstName . '</small><br><small>Response by: ' . $responseByFirstName . '</small>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'user-payreqs.fakturs.action')
            ->rawColumns(['action', 'amount', 'customer', 'invoice_info', 'faktur_info', 'users', 'remarks'])
            ->toJson();
    }

    public function notResponseYet()
    {
        // count faktur that not response yet
        $fakturs = Faktur::whereNull('faktur_no')->get();

        return $fakturs->count();
    }
}
