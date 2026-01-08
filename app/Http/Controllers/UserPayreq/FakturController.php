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
        $customers = Customer::orderBy('name', 'asc')->where('type', 'customer')->get();

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

        $customer = Customer::findOrFail($validatedData['customer_id']);
        
        if ($customer->code) {
            $sapBP = \App\Models\SapBusinessPartner::where('code', $customer->code)->first();
            
            if ($sapBP && $sapBP->credit_limit !== null && $sapBP->credit_limit > 0) {
                $invoiceAmount = (float) $validatedData['dpp'];
                $currentBalance = $sapBP->balance ?? 0;
                $availableCredit = $sapBP->credit_limit - $currentBalance;
                
                if ($invoiceAmount > $availableCredit) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "Credit limit exceeded! Available credit: " . number_format($availableCredit, 2) . ", Invoice amount: " . number_format($invoiceAmount, 2));
                }
            }
        }

        $validatedData['remarks'] = $request->remarks;
        $validatedData['created_by'] = auth()->user()->id;
        $validatedData['submit_at'] = now();
        $validatedData['create_date'] = now()->format('Y-m-d');
        $validatedData['type'] = 'sales';
        $validatedData['kurs'] = $request->kurs;

        Faktur::create($validatedData);

        return redirect()->route('user-payreqs.fakturs.index')->with('success', 'Faktur created successfully.');
    }

    public function update_arinvoice(Request $request, $id)
    {
        // return $request->all();
        $faktur = Faktur::findOrFail($id);

        $validatedData = $request->validate([
            'customer_id' => 'required',
            'invoice_no' => 'required',
            'invoice_date' => 'required',
            'dpp' => 'required',
        ]);

        $validatedData['remarks'] = $request->remarks;
        $validatedData['kurs'] = $request->kurs;

        $faktur->update($validatedData);

        return redirect()->route('user-payreqs.fakturs.index')->with('success', 'Faktur updated successfully.');
    }

    public function update_faktur(Request $request)
    {
        $faktur = Faktur::findOrFail($request->faktur_id);

        $validatedData = $request->validate([
            'faktur_no' => 'required',
            'faktur_date' => 'required',
            'ppn' => 'required',
        ]);

        $validatedData['response_by'] = auth()->user()->id;
        $validatedData['updated_at'] = now();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $filename = 'faktur_' . rand() . '.' . $extension;
            $file->move(public_path('faktur'), $filename);
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
        if (array_intersect(['admin', 'superadmin', 'tax_Officer', 'sales', 'cashier'], $getUserRoles)) {
            $fakturs = Faktur::where('type', 'sales')
                ->orderBy('invoice_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $fakturs = Faktur::where('type', 'sales')
                ->orderBy('invoice_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->where('created_by', auth()->user()->id)
                ->orWhere('response_by', auth()->user()->id)
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
                return '<small>' . number_format($faktur->dpp, 2) . '</small><br><small>' . number_format($faktur->ppn, 2) . '</small>';
            })
            ->addColumn('invoice_info', function ($faktur) {
                $invoice_date = $faktur->invoice_date ? \Carbon\Carbon::parse($faktur->invoice_date)->format('d-M-Y') : null;
                return '<small>No. ' . $faktur->invoice_no . '</small><br><small>Date: ' . $invoice_date . '</small>';
            })
            ->addColumn('faktur_info', function ($faktur) {
                $faktur_date = $faktur->faktur_date ? \Carbon\Carbon::parse($faktur->faktur_date)->format('d-M-Y') : '-';
                return '<small>No. ' . $faktur->faktur_no . '</small><br><small>Date: ' . $faktur_date . '</small>';
            })
            ->addColumn('users', function ($faktur) {
                $createdByFirstName = explode(' ', $faktur->created_by_name)[0];
                $responseByFirstName = explode(' ', $faktur->response_by_name)[0];
                return '<small>' . $createdByFirstName . '</small><br><small>' . $responseByFirstName . '</small>';
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
