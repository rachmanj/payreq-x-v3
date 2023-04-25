<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::whereNull('payment_date')
            ->orderBy('vendor_name', 'asc')
            ->get();

        return view('invoices.index', compact('invoices'));
    }

    public function paid_index()
    {
        return view('invoices.paid');
    }

    public function paid(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if ($request->payment_date) {
            $payment_date = $request->payment_date;
        } else {
            $payment_date = date('Y-m-d');
        }

        // update local db
        $invoice->payment_date = $payment_date;
        $invoice->save();


        // UPDATE REMOTE DB
        $url = 'http://192.168.33.18:8080/irr-support/api/invoices/';
        // $url = 'http://localhost:8000/api/invoices/';

        $client = new \GuzzleHttp\Client();
        $client->request('PUT', $url . $invoice->invoice_irr_id, [
            'form_params' => [
                'payment_date' => $payment_date,
            ]
        ]);

        if ($request->account_id) {
            // UPDATE ACCOUNT BALANCE
            $account = Account::find($request->account_id);
            $account->balance -= $invoice->amount;
            $account->save();
        }

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Payment Invoice ', $invoice->nomor_invoice);

        return redirect()->route('invoices.index')->with('success', 'Invoice has been updated');
    }

    public function multi_paid(Request $request)
    {
        $invoices = Invoice::whereIn('id', $request->invoices)->get();

        foreach ($invoices as $invoice) {
            // update local db
            $invoice->payment_date = $request->payment_date;
            $invoice->save();

            // UPDATE REMOTE DB
            $url = 'http://192.168.33.18:8080/irr-support/api/invoices/';
            // $url = 'http://localhost:5000/api/invoices/';
            $client = new \GuzzleHttp\Client();
            $client->request('PUT', $url . $invoice->invoice_irr_id, [
                'form_params' => [
                    'payment_date' => $request->payment_date,
                ]
            ]);

            if ($request->account_id) {
                // UPDATE ACCOUNT BALANCE
                $account = Account::find($request->account_id);
                $account->balance -= $invoice->amount;
                $account->save();
            }

            // SAVE ACTIVITY 
            $activityCtrl = app(ActivityController::class);
            $activityCtrl->store(auth()->user()->id, 'Payment Invoice ', $invoice->nomor_invoice);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice has been updated');
    }

    public function data()
    {
        $invoices = Invoice::whereNull('payment_date')
            ->orderBy('received_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return datatables()->of($invoices)
            ->editColumn('received_date', function ($invoices) {
                $received_date = new \DateTime($invoices->received_date);
                $today = new \DateTime(now()->addHours(8));
                $diff_days = $received_date->diff($today)->format('%a');

                return $invoices->received_date ? date('d-m-Y', strtotime($invoices->received_date)) . ' | ' . $diff_days : '-';
            })
            ->editColumn('created_at', function ($invoices) {
                $created_at = new \DateTime($invoices->created_at);
                $today = new \DateTime(now()->addHours(8));
                $diff_days = $created_at->diff($today)->format('%a');

                return $invoices->created_at ? date('d-m-Y', strtotime($invoices->created_at)) . ' | ' . $diff_days : '-';
            })
            ->editColumn('amount', function ($invoices) {
                return number_format($invoices->amount, 0);
            })
            ->addColumn('days', function ($invoices) {
                $received_date = new \DateTime($invoices->received_date);
                $today = new \DateTime(now());
                $diff_days = $received_date->diff($today)->format('%a');

                return $diff_days;
            })
            ->addIndexColumn()
            ->addColumn('action', 'invoices.action')
            ->rawColumns(['action'])
            ->toJson();
    }

    public function paid_data()
    {
        $invoices = Invoice::whereNotNull('payment_date')->orderBy('payment_date', 'desc')->get();

        return datatables()->of($invoices)
            ->editColumn('created_at', function ($invoices) {
                return $invoices->created_at ? date('d-m-Y', strtotime($invoices->created_at)) : '-';
            })
            ->editColumn('payment_date', function ($invoices) {
                return $invoices->payment_date ? date('d-m-Y', strtotime($invoices->payment_date)) : '-';
            })
            ->editColumn('amount', function ($invoices) {
                return number_format($invoices->amount, 2);
            })
            // diff days
            ->addColumn('days', function ($invoices) {
                $received_date = new \DateTime($invoices->created_at);
                $payment_date = new \DateTime($invoices->payment_date);
                $diff_days = $received_date->diff($payment_date)->format('%a');

                return $diff_days;
            })
            ->addIndexColumn()
            ->toJson();
    }
}
