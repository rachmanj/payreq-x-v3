<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Faktur;
use App\Models\Customer;
use App\Models\SapSubmissionLog;
use App\Services\SapService;
use App\Services\SapArInvoiceBuilder;
use App\Services\SapArInvoiceJeBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VatController extends Controller
{
    public function index()
    {
        $page = request()->query('page', 'dashboard');
        $status = request()->query('status');

        $count_data = $this->generate_count_data();
        $amount_data = $this->generate_amount_data();

        $views = [
            'dashboard' => 'accounting.vat.dashboard',
            'search' => 'accounting.vat.search',
            'purchase' => $status == 'incomplete' ? 'accounting.vat.ap.incomplete' : 'accounting.vat.ap.complete',
            'sales' => $status == 'incomplete' ? 'accounting.vat.ar.incomplete' : 'accounting.vat.ar.complete',
        ];

        if ($page === 'search') {
            $customers = Customer::orderBy('name')->get();
            return view($views[$page], compact('customers'));
        }

        if ($page === 'dashboard') {
            return view($views[$page], compact('amount_data', 'count_data'));
        }

        return view($views[$page] ?? $views['default']);
    }

    public function purchase_update(Request $request, $id)
    {
        $document = Faktur::findOrFail($id);
        $document->response_by = auth()->user()->id;
        $document->response_at = now();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $filename = 'faktur_' . uniqid() . '.' . $extension;
            $file->move(public_path('faktur'), $filename);
            $document->attachment = $filename;
        }

        $document->save();

        return redirect()->back()->with('success', 'Faktur updated successfully');
    }

    public function sales_update(Request $request, $id)
    {
        $existingDocument = Faktur::where('doc_num', $request->input('doc_num'))->first();
        if ($existingDocument && $existingDocument->id != $id) {
            return redirect()->back()->with('error', 'Document number already exists.');
        }

        $document = Faktur::findOrFail($id);
        $document->doc_num = $request->input('doc_num');
        $document->posting_date = $request->input('posting_date');
        $document->user_code = auth()->user()->username;
        $document->save();

        return redirect()->back()->with('success', 'Document number and posting date updated successfully');
    }

    public function data()
    {
        $page = request()->query('page');
        $status = request()->query('status');

        $query = Faktur::query();

        if ($page === 'purchase') {
            $query->where('type', 'purchase');
            $action_button = $status === 'incomplete' ? 'accounting.vat.ap.action' : 'accounting.vat.ap.action_complete';

            if ($status === 'incomplete') {
                $query->whereNull('attachment');
            } else {
                $query->whereNotNull('attachment');
            }
        } else {
            $query->where('type', 'sales');
            $action_button = $status === 'incomplete' ? 'accounting.vat.ar.action' : 'accounting.vat.ar.action_complete';

            if ($status === 'incomplete') {
                // Show documents NOT posted to SAP yet
                $query->whereNull('sap_ar_doc_num');
            } else {
                // Show documents already submitted/posted to SAP
                $query->whereNotNull('sap_ar_doc_num');
            }
        }

        $documents = $query->orderBy('create_date', 'desc')->get();

        return datatables()->of($documents)
            ->addColumn('amount', function ($document) {
                $dpp = number_format($document->dpp, 2);
                $ppn = number_format($document->ppn, 2);
                return '<small>DPP: ' . $dpp . '</small><br><small>PPN: ' . $ppn . '</small>';
            })
            ->editColumn('create_date', function ($document) {
                return date('d-M-Y', strtotime($document->create_date));
            })
            ->editColumn('posting_date', function ($document) {
                return date('d-M-Y', strtotime($document->posting_date));
            })
            ->addColumn('invoice', function ($document) {
                return '<small>No.' . $document->invoice_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->invoice_date)) . '</small>';
            })
            ->addColumn('faktur', function ($document) {
                if (is_null($document->faktur_date)) {
                    return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl. - </small>';
                }
                return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->faktur_date)) . '</small>';
            })
            ->addColumn('customer', function ($document) {
                return '<small>' . $document->customer->name . '</small>';
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
            ->editColumn('updated_by', function ($document) {
                $updatedAt = Carbon::parse($document->updated_at)->addHours(8)->format('d-M-Y H:i');
                return '<small>' . $document->updated_by . '</small><br><small>at ' . $updatedAt . '</small>';
            })
            ->addColumn('doc_date', function ($document) {
                $createDate = Carbon::parse($document->create_date)->format('d-M-Y');
                $postingDate = Carbon::parse($document->posting_date)->format('d-M-Y');
                return $createDate . '<br>' . $postingDate;
            })
            ->addColumn('sales_days', function ($document) {
                $today = date('Y-m-d');
                $diff = date_diff(date_create($document->invoice_date), date_create($today));
                return $diff->format('%a');
            })
            ->addColumn('sap_ar_doc_num', function ($document) {
                return $document->sap_ar_doc_num ?? '-';
            })
            ->addColumn('sap_je_num', function ($document) {
                return $document->sap_je_num ?? '-';
            })
            ->addColumn('action', $action_button)
            ->addIndexColumn()
            ->rawColumns(['remarks', 'action', 'updated_by', 'amount', 'invoice', 'customer', 'faktur'])
            ->toJson();
    }

    public function generate_count_data()
    {
        $years = DB::table('fakturs')
            ->select(DB::raw('DISTINCT YEAR(create_date) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
        ];

        $data = [];

        foreach ($years as $year) {
            $yearData = [
                'year' => $year,
                'purchase' => [
                    'total' => 0,
                    'percent_complete' => 0,
                    'outstanding' => 0,
                    'complete' => 0
                ],
                'sales' => [
                    'total' => 0,
                    'percent_complete' => 0,
                    'outstanding' => 0,
                    'complete' => 0
                ],
                'data' => []
            ];

            $total_purchase_outstanding = 0;
            $total_purchase_complete = 0;
            $total_sales_outstanding = 0;
            $total_sales_complete = 0;

            foreach ($months as $month => $monthName) {

                $purchase_outstanding = $this->count_outstanding_monthly($year, $month, 'purchase');
                $purchase_complete = $this->count_complete_monthly($year, $month, 'purchase');
                $sales_outstanding = $this->count_outstanding_sales_monthly($year, $month, 'sales');
                $sales_complete = $this->count_complete_sales_monthly($year, $month, 'sales');

                $monthData = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'purchase' => [
                        'outstanding' => $purchase_outstanding,
                        'complete' => $purchase_complete,
                        'percent' => $purchase_outstanding + $purchase_complete > 0 ? number_format($purchase_complete / ($purchase_outstanding + $purchase_complete) * 100, 1) : 0
                    ],
                    'sales' => [
                        'outstanding' => $sales_outstanding,
                        'complete' => $sales_complete,
                        'percent' => $sales_outstanding + $sales_complete > 0 ? number_format($sales_complete / ($sales_outstanding + $sales_complete) * 100, 1) : 0
                    ]
                ];

                $yearData['data'][] = $monthData;

                // Tambahkan jumlah bulanan ke jumlah tahunan
                $yearData['purchase']['total'] += $purchase_outstanding + $purchase_complete;
                $yearData['sales']['total'] += $sales_outstanding + $sales_complete;

                // Tambahkan ke total tahunan
                $total_purchase_outstanding += $purchase_outstanding;
                $total_purchase_complete += $purchase_complete;
                $total_sales_outstanding += $sales_outstanding;
                $total_sales_complete += $sales_complete;
            }

            // Hitung persentase penyelesaian pembelian tahunan
            $yearData['purchase']['outstanding'] = $total_purchase_outstanding;
            $yearData['purchase']['complete'] = $total_purchase_complete;
            $yearData['purchase']['percent_complete'] = $total_purchase_outstanding + $total_purchase_complete > 0 ? number_format($total_purchase_complete / ($total_purchase_outstanding + $total_purchase_complete) * 100, 1) : 0;

            // Hitung persentase penyelesaian penjualan tahunan
            $yearData['sales']['outstanding'] = $total_sales_outstanding;
            $yearData['sales']['complete'] = $total_sales_complete;
            $yearData['sales']['percent_complete'] = $total_sales_outstanding + $total_sales_complete > 0 ? number_format($total_sales_complete / ($total_sales_outstanding + $total_sales_complete) * 100, 1) : 0;

            $data[] = $yearData;
        }

        return $data;
    }

    public function generate_amount_data()
    {
        $years = DB::table('fakturs')
            ->select(DB::raw('DISTINCT YEAR(faktur_date) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
        ];

        $data = [];

        foreach ($years as $year) {
            $yearData = [
                'year' => $year,
                'sales' => 0,
                'purchase' => 0,
                'difference' => 0,
                'data' => []
            ];

            foreach ($months as $month => $monthName) {

                $monthData = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'sales' => number_format($this->sum_amount_monthly($year, $month, 'sales') / 1000, 2),
                    'purchase' => number_format($this->sum_amount_monthly($year, $month, 'purchase') / 1000, 2),
                    'difference' => number_format($this->calculate_difference_monthly($year, $month) / 1000, 2)
                ];

                $yearData['data'][] = $monthData;
            }

            // Format jumlah tahunan
            $yearData['sales'] = number_format($this->sum_amount_yearly($year, 'sales') / 1000, 2);
            $yearData['purchase'] = number_format($this->sum_amount_yearly($year, 'purchase') / 1000, 2);
            $yearData['difference'] = number_format($this->calculate_difference_yearly($year) / 1000, 2);

            $data[] = $yearData;
        }

        return $data;
    }

    public function search_data()
    {
        // Return empty data if search hasn't been clicked
        if (!request('search_clicked')) {
            return datatables()->of([])->addIndexColumn()->toJson();
        }

        $query = Faktur::query()
            ->with('customer');

        if (request('faktur_no')) {
            $query->where('faktur_no', 'like', '%' . request('faktur_no') . '%');
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('invoice_no')) {
            $query->where('invoice_no', 'like', '%' . request('invoice_no') . '%');
        }

        if (request('customer_name')) {
            $query->where('customer_id', request('customer_name'));
        }

        if (request('doc_num')) {
            $query->where('doc_num', 'like', '%' . request('doc_num') . '%');
        }

        return datatables()->of($query)
            ->addColumn('amount', function ($document) {
                $dpp = number_format($document->dpp, 2);
                $ppn = number_format($document->ppn, 2);
                return '<small>DPP: ' . $dpp . '</small><br><small>PPN: ' . $ppn . '</small>';
            })
            ->editColumn('create_date', function ($document) {
                return date('d-M-Y', strtotime($document->create_date));
            })
            ->addColumn('invoice', function ($document) {
                if (is_null($document->invoice_no)) {
                    return '<small>No. - </small><br><small>Tgl. - </small>';
                }
                if (is_null($document->invoice_date)) {
                    return '<small>No.' . $document->invoice_no . '</small><br><small>Tgl. - </small>';
                }
                return '<small>No.' . $document->invoice_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->invoice_date)) . '</small>';
            })
            ->addColumn('faktur', function ($document) {
                if (is_null($document->faktur_date)) {
                    return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl. - </small>';
                }
                return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->faktur_date)) . '</small>';
            })
            ->addColumn('customer', function ($document) {
                return '<small>' . $document->customer->name . '</small>';
            })
            ->addColumn('action', function ($document) {
                $showButton = '<a href="' . route('accounting.vat.show', $document->id) . '" class="btn btn-xs btn-success">show</a>';

                $attachmentButton = '';
                if ($document->attachment) {
                    $attachmentButton = ' <a href="' . $document->attachment . '" target="_blank" class="btn btn-xs btn-info"><i class="fas fa-paperclip"></i></a>';
                }

                return $showButton . $attachmentButton;
            })
            ->addIndexColumn()
            ->rawColumns(['amount', 'invoice', 'customer', 'faktur', 'action'])
            ->toJson();
    }

    private function sum_amount_monthly($year, $month, $type)
    {
        return Faktur::whereYear('faktur_date', $year)
            ->whereMonth('faktur_date', $month)
            ->where('type', $type)
            ->sum('ppn');
    }

    private function calculate_difference_monthly($year, $month)
    {
        $sales = $this->sum_amount_monthly($year, $month, 'sales');
        $purchase = $this->sum_amount_monthly($year, $month, 'purchase');
        return $purchase - $sales;
    }

    private function sum_amount_yearly($year, $type)
    {
        return Faktur::whereYear('faktur_date', $year)
            ->where('type', $type)
            ->sum('ppn');
    }

    private function calculate_difference_yearly($year)
    {
        $sales = $this->sum_amount_yearly($year, 'sales');
        $purchase = $this->sum_amount_yearly($year, 'purchase');
        return $purchase - $sales;
    }

    private function count_complete_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNotNull('attachment')
            ->count();
    }

    private function count_outstanding_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNull('attachment')
            ->count();
    }

    private function count_outstanding_sales_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNull('doc_num')
            ->count();
    }

    private function count_complete_sales_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNotNull('doc_num')
            ->count();
    }

    public function show(Faktur $faktur)
    {
        return view('accounting.vat.show', compact('faktur'));
    }

    public function update(Request $request, Faktur $faktur)
    {
        try {
            if ($faktur->type === 'purchase') {
                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $extension = $file->getClientOriginalExtension();
                    $filename = 'faktur_' . uniqid() . '.' . $extension;
                    $file->move(public_path('faktur'), $filename);

                    $faktur->attachment = $filename;
                    $faktur->response_by = auth()->user()->id;
                    $faktur->response_at = now();
                    $faktur->save();

                    return redirect()->back()->with('success', 'File uploaded successfully');
                }
                return redirect()->back()->with('error', 'No file uploaded');
            } else {
                // For sales type
                $request->validate([
                    'doc_num' => 'required|string|max:255',
                ]);

                // Check if doc_num already exists
                $existingFaktur = Faktur::where('doc_num', $request->doc_num)
                    ->where('id', '!=', $faktur->id)
                    ->first();

                if ($existingFaktur) {
                    return redirect()->back()->with('error', 'Document number already exists');
                }

                $faktur->doc_num = $request->doc_num;
                $faktur->user_code = auth()->user()->username;
                $faktur->save();

                return redirect()->back()->with('success', 'Document number updated successfully');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the record');
        }
    }

    public function previewSapSubmission(Faktur $faktur)
    {
        // Permission check
        if (!auth()->user()->can('submit-sap-ar-invoice')) {
            abort(403, 'Unauthorized action.');
        }

        // Validate faktur can be submitted
        $arInvoiceBuilder = new SapArInvoiceBuilder($faktur);
        $arErrors = $arInvoiceBuilder->validate();
        if (!empty($arErrors)) {
            return redirect()->back()->with('error', 'Validation failed: ' . implode(', ', $arErrors));
        }

        // Get service item code
        $sapService = app(SapService::class);
        $serviceItems = $sapService->getServiceItems();
        $itemCode = !empty($serviceItems) ? ($serviceItems[0]['ItemCode'] ?? null) : null;
        if (empty($itemCode)) {
            $itemCode = config('services.sap.ar_invoice.default_item_code', 'SERVICE');
        }

        // Build preview data
        $arInvoiceBuilder = new SapArInvoiceBuilder($faktur, $itemCode);
        $arPreviewData = $arInvoiceBuilder->getPreviewData();

        // Build JE preview data with default dates (previous EOM)
        $invoiceDate = \Carbon\Carbon::parse($faktur->invoice_date);
        $defaultJePostingDate = $faktur->je_posting_date 
            ? \Carbon\Carbon::parse($faktur->je_posting_date)
            : $invoiceDate->copy()->subMonth()->endOfMonth();
        $defaultJeTaxDate = $faktur->je_tax_date 
            ? \Carbon\Carbon::parse($faktur->je_tax_date)
            : $defaultJePostingDate;
        $defaultJeDueDate = $faktur->je_due_date 
            ? \Carbon\Carbon::parse($faktur->je_due_date)
            : $defaultJePostingDate;

        $jeBuilder = new SapArInvoiceJeBuilder($faktur, $defaultJePostingDate, $defaultJeTaxDate, $defaultJeDueDate);
        $jePreviewData = $jeBuilder->getPreviewData();

        return view('accounting.vat.ar.sap_preview', [
            'faktur' => $faktur,
            'arPreview' => $arPreviewData['ar_invoice'],
            'jePreview' => $jePreviewData['journal_entry'],
            'itemCode' => $itemCode,
        ]);
    }

    public function updateSapPreview(Request $request, Faktur $faktur)
    {
        // Permission check
        if (!auth()->user()->can('submit-sap-ar-invoice')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        // Determine which section is being updated
        $updateType = $request->input('update_type', 'ar_invoice'); // 'ar_invoice' or 'journal_entry'

        if ($updateType === 'ar_invoice') {
            // Validate AR Invoice fields
            $request->validate([
                'invoice_no' => 'required|string|max:255',
                'faktur_no' => 'required|string|max:255',
                'faktur_date' => 'required|date',
            ]);

            // Update faktur fields
            $faktur->invoice_no = $request->invoice_no;
            $faktur->faktur_no = $request->faktur_no;
            $faktur->faktur_date = $request->faktur_date;
            $faktur->save();

            return response()->json([
                'success' => true,
                'message' => 'AR Invoice details updated successfully.',
                'data' => [
                    'invoice_no' => $faktur->invoice_no,
                    'faktur_no' => $faktur->faktur_no,
                    'faktur_date' => $faktur->faktur_date ? \Carbon\Carbon::parse($faktur->faktur_date)->format('Y-m-d') : null,
                ],
            ]);
        } else {
            // Validate Journal Entry fields
            $request->validate([
                'je_posting_date' => 'required|date',
                'je_tax_date' => 'required|date',
                'je_due_date' => 'required|date',
            ]);

            // Update Journal Entry dates
            $faktur->je_posting_date = $request->je_posting_date;
            $faktur->je_tax_date = $request->je_tax_date;
            $faktur->je_due_date = $request->je_due_date;
            
            // Update revenue account if provided
            if ($request->has('revenue_account_code')) {
                $validAccounts = ['41101', '41201'];
                if (in_array($request->revenue_account_code, $validAccounts)) {
                    $faktur->revenue_account_code = $request->revenue_account_code;
                }
            }
            
            $faktur->save();

            return response()->json([
                'success' => true,
                'message' => 'Journal Entry details updated successfully.',
                'data' => [
                    'je_posting_date' => $faktur->je_posting_date ? \Carbon\Carbon::parse($faktur->je_posting_date)->format('Y-m-d') : null,
                    'je_tax_date' => $faktur->je_tax_date ? \Carbon\Carbon::parse($faktur->je_tax_date)->format('Y-m-d') : null,
                    'je_due_date' => $faktur->je_due_date ? \Carbon\Carbon::parse($faktur->je_due_date)->format('Y-m-d') : null,
                    'revenue_account_code' => $faktur->revenue_account_code,
                ],
            ]);
        }
    }

    public function submitToSap(Request $request, Faktur $faktur)
    {
        // Permission check
        if (!auth()->user()->can('submit-sap-ar-invoice')) {
            abort(403, 'Unauthorized action.');
        }

        // Validate and update editable fields
        if ($request->has('invoice_no') && !empty($request->invoice_no)) {
            $faktur->invoice_no = $request->invoice_no;
        }
        if ($request->has('faktur_no') && !empty($request->faktur_no)) {
            $faktur->faktur_no = $request->faktur_no;
        }
        if ($request->has('faktur_date') && !empty($request->faktur_date)) {
            $faktur->faktur_date = $request->faktur_date;
        }

        // Validate revenue account code if provided
        if ($request->has('revenue_account_code')) {
            $validAccounts = ['41101', '41201'];
            if (!in_array($request->revenue_account_code, $validAccounts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid revenue account code. Must be 41101 or 41201.',
                ], 422);
            }
            $faktur->revenue_account_code = $request->revenue_account_code;
        }

        // Set default revenue account if not set
        if (empty($faktur->revenue_account_code)) {
            $faktur->revenue_account_code = config('services.sap.ar_invoice.default_revenue_account', '41101');
        }

        // Set project from request or customer
        if ($request->has('project') && !empty($request->project)) {
            $faktur->project = $request->project;
        } elseif (empty($faktur->project)) {
            $faktur->project = $faktur->customer->project;
        }

        // Set JE dates from request
        if ($request->has('je_posting_date') && !empty($request->je_posting_date)) {
            $faktur->je_posting_date = $request->je_posting_date;
        }
        if ($request->has('je_tax_date') && !empty($request->je_tax_date)) {
            $faktur->je_tax_date = $request->je_tax_date;
        }
        if ($request->has('je_due_date') && !empty($request->je_due_date)) {
            $faktur->je_due_date = $request->je_due_date;
        }

        // Department will be taken from customer's default_department_code (Option B)
        // No need to set department_id on faktur

        $faktur->save();

        DB::beginTransaction();
        $itemCode = null; // Initialize for error handling
        try {
            // Step 1: Get valid service item code from SAP B1
            $sapService = app(SapService::class);
            $serviceItems = $sapService->getServiceItems();
            
            if (!empty($serviceItems)) {
                // Use the first service item found
                $itemCode = $serviceItems[0]['ItemCode'] ?? null;
            }
            
            // Fallback to configured default if no service items found
            if (empty($itemCode)) {
                $itemCode = config('services.sap.ar_invoice.default_item_code', 'SERVICE');
            }

            // Step 2: Build and validate AR Invoice
            $arInvoiceBuilder = new SapArInvoiceBuilder($faktur, $itemCode);
            $arErrors = $arInvoiceBuilder->validate();

            if (!empty($arErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $arErrors),
                ], 422);
            }

            $arInvoiceData = $arInvoiceBuilder->build();

            // Step 3: Create AR Invoice in SAP B1
            $arResult = $sapService->createArInvoice($arInvoiceData);

            if (!($arResult['success'] ?? false)) {
                throw new \Exception('Failed to create AR Invoice: ' . ($arResult['message'] ?? 'Unknown error'));
            }

            // Step 3: Update faktur with AR Invoice info
            $faktur->sap_ar_doc_num = $arResult['doc_num'];
            $faktur->sap_ar_doc_entry = $arResult['doc_entry'];
            $faktur->sap_submission_status = 'ar_created';
            $faktur->sap_submission_attempts = ($faktur->sap_submission_attempts ?? 0) + 1;
            $faktur->save();

            // Log AR Invoice creation
            SapSubmissionLog::create([
                'faktur_id' => $faktur->id,
                'document_type' => 'ar_invoice',
                'status' => 'success',
                'sap_doc_num' => $arResult['doc_num'],
                'sap_doc_entry' => $arResult['doc_entry'],
                'sap_response' => json_encode($arResult['data']),
                'attempt_number' => $faktur->sap_submission_attempts,
                'submitted_by' => auth()->id(),
            ]);

            // Step 4: Build and validate Journal Entry with custom dates
            $jePostingDate = $faktur->je_posting_date 
                ? \Carbon\Carbon::parse($faktur->je_posting_date)
                : null;
            $jeTaxDate = $faktur->je_tax_date 
                ? \Carbon\Carbon::parse($faktur->je_tax_date)
                : null;
            $jeDueDate = $faktur->je_due_date 
                ? \Carbon\Carbon::parse($faktur->je_due_date)
                : null;

            $jeBuilder = new SapArInvoiceJeBuilder($faktur, $jePostingDate, $jeTaxDate, $jeDueDate);
            $jeErrors = $jeBuilder->validate();

            if (!empty($jeErrors)) {
                // AR Invoice created but JE validation failed - mark as partial
                $faktur->sap_submission_status = 'ar_created';
                $faktur->sap_submission_error = 'JE Validation failed: ' . implode(', ', $jeErrors);
                $faktur->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'partial' => true,
                    'message' => 'AR Invoice created successfully, but Journal Entry validation failed: ' . implode(', ', $jeErrors),
                    'ar_doc_num' => $arResult['doc_num'],
                ], 422);
            }

            $jeData = $jeBuilder->build();

            // Step 5: Create Journal Entry in SAP B1
            $jeResult = $sapService->createJournalEntry($jeData);

            if (!($jeResult['success'] ?? false)) {
                // AR Invoice created but JE creation failed - mark as partial
                $faktur->sap_submission_status = 'ar_created';
                $faktur->sap_submission_error = 'JE Creation failed: ' . ($jeResult['message'] ?? 'Unknown error');
                $faktur->save();

                // Log JE failure
                SapSubmissionLog::create([
                    'faktur_id' => $faktur->id,
                    'document_type' => 'journal_entry',
                    'status' => 'failed',
                    'sap_error' => $jeResult['message'] ?? 'Unknown error',
                    'attempt_number' => $faktur->sap_submission_attempts,
                    'submitted_by' => auth()->id(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'partial' => true,
                    'message' => 'AR Invoice created successfully, but Journal Entry creation failed: ' . ($jeResult['message'] ?? 'Unknown error'),
                    'ar_doc_num' => $arResult['doc_num'],
                ], 422);
            }

            // Step 6: Update faktur with Journal Entry info
            $faktur->sap_je_num = $jeResult['journal_number'];
            $faktur->sap_je_doc_entry = $jeResult['doc_entry'];
            $faktur->sap_submission_status = 'completed';
            $faktur->sap_submitted_at = now();
            $faktur->sap_submitted_by = auth()->id();
            $faktur->sap_submission_error = null;
            $faktur->save();

            // Log Journal Entry creation
            SapSubmissionLog::create([
                'faktur_id' => $faktur->id,
                'document_type' => 'journal_entry',
                'status' => 'success',
                'sap_journal_number' => $jeResult['journal_number'],
                'sap_doc_entry' => $jeResult['doc_entry'],
                'sap_response' => json_encode($jeResult['data']),
                'attempt_number' => $faktur->sap_submission_attempts,
                'submitted_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'AR Invoice and Journal Entry created successfully',
                'ar_doc_num' => $arResult['doc_num'],
                'je_num' => $jeResult['journal_number'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Get the ItemCode that was attempted
            $itemCode = $itemCode ?? config('services.sap.ar_invoice.default_item_code', 'SERVICE');
            $errorMessage = $e->getMessage();
            
            // Check for specific SAP B1 errors and provide helpful guidance
            if (stripos($errorMessage, 'Business partner catalog number linked to item not specified as sales item') !== false) {
                $customerCode = $faktur->customer->code ?? 'N/A';
                $errorMessage = "SAP B1 Error: The item '{$itemCode}' is not configured as a sales item for customer '{$customerCode}'. " .
                    "To fix this in SAP B1:\n" .
                    "1. Go to Business Partners → Select customer '{$customerCode}'\n" .
                    "2. Go to the 'Items' tab\n" .
                    "3. Add item '{$itemCode}' to the customer's item catalog\n" .
                    "4. Ensure it's marked as a 'Sales Item'\n" .
                    "5. Save the customer master data\n" .
                    "Then try submitting again. (Attempted ItemCode: '{$itemCode}')";
            } elseif (stripos($errorMessage, 'item number') !== false || stripos($errorMessage, 'itemcode') !== false) {
                $errorMessage .= " (Attempted ItemCode: '{$itemCode}'). Please configure a valid service item code in your .env file using SAP_AR_INVOICE_DEFAULT_ITEM_CODE.";
            }

            // Log error
            Log::error('SAP AR Invoice submission failed', [
                'faktur_id' => $faktur->id,
                'error' => $e->getMessage(),
                'item_code_attempted' => $itemCode,
                'trace' => $e->getTraceAsString(),
            ]);

            // Update faktur with error
            $faktur->sap_submission_status = 'failed';
            $faktur->sap_submission_error = $errorMessage;
            $faktur->sap_submission_attempts = ($faktur->sap_submission_attempts ?? 0) + 1;
            $faktur->save();

            // Log failure
            SapSubmissionLog::create([
                'faktur_id' => $faktur->id,
                'document_type' => 'ar_invoice',
                'status' => 'failed',
                'sap_error' => $errorMessage,
                'attempt_number' => $faktur->sap_submission_attempts,
                'submitted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit to SAP B1: ' . $errorMessage,
            ], 500);
        }
    }
}
