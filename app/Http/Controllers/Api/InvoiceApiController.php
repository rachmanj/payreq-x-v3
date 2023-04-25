<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceApiController extends Controller
{
    public function store(Request $request)
    {
        // VALIDATION RULES
        $rules = [
            'nomor_invoice' => 'required',
            'vendor_name' => 'required',
            'received_date' => 'required',
            'amount' => 'required',
        ];

        // CREATE VALIDATOR INSTANCE
        $validator = Validator::make($request->all(), $rules);

        // VALIDATE REQUEST
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        // CREATE INVOICE
        $invoice = Invoice::create([
            'nomor_invoice' => $request->nomor_invoice,
            'invoice_irr_id' => $request->invoice_irr_id,
            'vendor_name' => $request->vendor_name,
            'received_date' => $request->received_date,
            'amount' => $request->amount,
            'origin' => 'IRR',
            'remarks' => $request->remarks,
            'sender_name' => $request->sender_name,
        ]);

        // RETURN RESPONSE
        return response()->json([
            'status' => 'success',
            'message' => 'Invoice created successfully',
            'data' => $invoice,
        ], 200);
    }
}
