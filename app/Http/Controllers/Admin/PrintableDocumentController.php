<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payreq;
use App\Models\Realization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintableDocumentController extends Controller
{
    public function index()
    {
        return view('admin.printable-documents.index');
    }

    public function data(Request $request)
    {
        // Optimized query using raw DB query builder with joins
        // Performance improvements:
        // 1. Direct SQL joins instead of Eloquent relationships (eliminates N+1 queries)
        // 2. COALESCE for null handling at database level
        // 3. Specific column selection to reduce memory usage
        // 4. Direct column search without whereHas (faster execution)
        // 5. Pre-calculated date formatting at database level
        $query = DB::table('payreqs as p')
            ->select([
                'p.id',
                'p.nomor',
                'p.type',
                'p.status',
                'p.amount',
                'p.remarks',
                'p.approved_at',
                'p.canceled_at',
                'p.updated_at',
                'p.created_at',
                'p.printable as payreq_printable',
                DB::raw('COALESCE(r.nomor, "n/a") as realization_no'),
                DB::raw('COALESCE(r.printable, 0) as realization_printable'),
                DB::raw('COALESCE(u.name, "N/A") as requestor_name'),
                // Pre-calculate formatted dates at database level
                DB::raw('DATE_FORMAT(DATE_ADD(p.updated_at, INTERVAL 8 HOUR), "%d-%b-%Y %H:%i") as formatted_updated_at'),
                DB::raw('DATE_FORMAT(DATE_ADD(p.canceled_at, INTERVAL 8 HOUR), "%d-%b-%Y %H:%i") as formatted_canceled_at'),
                // Pre-calculate duration in days
                DB::raw('CASE WHEN p.approved_at IS NOT NULL THEN DATEDIFF(p.updated_at, p.approved_at) ELSE NULL END as duration_days')
            ])
            ->leftJoin('realizations as r', 'p.id', '=', 'r.payreq_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.id')
            ->where('p.status', '=', 'close') // Use = instead of whereIn for single value
            ->orderBy('p.created_at', 'desc');

        return datatables()->of($query)
            ->editColumn('nomor', function ($row) {
                return '<a href="#" style="color: black" title="' . e($row->remarks) . '">' . e($row->nomor) . '</a>';
            })
            ->editColumn('type', function ($row) {
                return ucfirst($row->type);
            })
            ->editColumn('realization_no', function ($row) {
                return $row->realization_no;
            })
            ->editColumn('requestor_name', function ($row) {
                return $row->requestor_name;
            })
            ->editColumn('status', function ($row) {
                if ($row->status === 'canceled') {
                    return '<button class="badge badge-danger">CANCELED</button> at ' . $row->formatted_canceled_at . ' wita';
                } else {
                    return '<button class="badge badge-success">CLOSE</button> at ' . $row->formatted_updated_at . ' wita';
                }
            })
            ->addColumn('duration', function ($row) {
                return $row->duration_days ?? 'N/A';
            })
            ->editColumn('amount', function ($row) {
                return number_format($row->amount, 2);
            })
            ->addColumn('action', function ($row) {
                // Create a simple object for the view - maintaining original UI
                $payreq = (object) [
                    'id' => $row->id,
                    'type' => $row->type,
                    'printable' => $row->payreq_printable,
                    'realization' => $row->realization_no !== 'n/a' ? (object) ['printable' => $row->realization_printable] : null
                ];
                return view('admin.printable-documents.action', compact('payreq'));
            })
            // Optimized search - use more efficient query patterns
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];

                    // Use more efficient search with proper escaping
                    $searchValue = '%' . $searchValue . '%';

                    $query->where(function ($q) use ($searchValue) {
                        $q->where('p.nomor', 'like', $searchValue)
                            ->orWhere('p.type', 'like', $searchValue)
                            ->orWhere('r.nomor', 'like', $searchValue)
                            ->orWhere('u.name', 'like', $searchValue);

                        // Only search numeric fields if search value is numeric
                        if (is_numeric(str_replace(['%', ',', '.'], '', $searchValue))) {
                            $numericSearch = str_replace(['%', ','], '', $searchValue);
                            $q->orWhere('p.amount', 'like', $numericSearch . '%');
                        }
                    });
                }
            })
            ->rawColumns(['nomor', 'status', 'action'])
            ->addIndexColumn()
            ->toJson();
    }

    public function updatePrintable(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'printable' => 'required'
        ]);

        try {
            // Convert printable to boolean - simple 0/1 conversion
            $printable = (int) $request->printable === 1;

            // Find the payreq document
            $payreq = Payreq::with('realization')->findOrFail($request->id);

            // Logic based on document type:
            // - advance: update realization printable
            // - reimburse: update payreq printable
            if ($payreq->type === 'advance') {
                // For advance type, update realization printable
                if ($payreq->realization) {
                    $payreq->realization->update(['printable' => $printable]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Realization not found for this advance document'
                    ], 404);
                }
            } elseif ($payreq->type === 'reimburse') {
                // For reimburse type, update payreq printable
                $payreq->update(['printable' => $printable]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Document type not supported for printable update'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Printable status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update printable status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdatePrintable(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'type' => 'required|in:payreq,realization',
            'printable' => 'required|boolean'
        ]);

        try {
            if ($request->type === 'payreq') {
                Payreq::whereIn('id', $request->ids)->update(['printable' => $request->printable]);
            } else {
                Realization::whereIn('id', $request->ids)->update(['printable' => $request->printable]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk update successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update: ' . $e->getMessage()
            ], 500);
        }
    }
}
