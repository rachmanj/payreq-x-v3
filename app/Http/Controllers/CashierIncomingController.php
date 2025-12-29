<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Cashier\TransaksiController;
use App\Models\Incoming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierIncomingController extends Controller
{
    public function index()
    {
        return view('cashier.incomings.index');
    }

    public function received_index()
    {
        return view('cashier.incomings.received.index');
    }

    public function receive(Request $request)
    {
        DB::beginTransaction();

        try {
            // update incomings table
            $incoming = Incoming::findOrFail($request->incoming_id);
            $incoming->receive_date = $request->receive_date;
            $incoming->cashier_id = auth()->user()->id;
            $incoming->save();

            // update app_balance in accounts table
            app(AccountController::class)->incoming($incoming->amount);

            // create transaksi
            app(TransaksiController::class)->store('incoming', $incoming);

            DB::commit();
            return redirect()->back()->with('success', 'Incoming has been received');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to receive incoming: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('cashier.incomings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
        ]);

        $incoming = new Incoming();
        $incoming->cashier_id = auth()->user()->id;
        $incoming->description = $request->description;
        $incoming->amount = $request->amount;
        $incoming->project = auth()->user()->project;
        if ($request->has('will_post')) {
            $incoming->will_post = 0;
        }
        $incoming->save();

        return redirect()->route('cashier.incomings.index')->with('success', 'Incoming has been created');
    }

    public function edit_received_date(Request $request, $id)
    {
        $incoming = Incoming::findOrFail($id);
        $incoming->receive_date = $request->receive_date;
        $incoming->save();

        return $this->received_index()->with('success', 'Receive date has been updated');
    }

    public function destroy($id)
    {
        $incoming = Incoming::findOrFail($id);
        $incoming->delete();

        return $this->index()->with('success', 'Incoming has been deleted');
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $incomings = Incoming::with([
                    'realization.requestor.department',
                    'realization.payreq',
                    'cashier',
                    'account'
                ])
                ->whereNull('receive_date')
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif (in_array('cashier', $userRoles)) {
            $incomings = Incoming::with([
                    'realization.requestor.department',
                    'realization.payreq',
                    'cashier',
                    'account'
                ])
                ->whereNull('receive_date')
                ->whereIn('project', ['000H', 'APS'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $incomings = Incoming::with([
                    'realization.requestor.department',
                    'realization.payreq',
                    'cashier',
                    'account'
                ])
                ->where('project', auth()->user()->project)
                ->whereNull('receive_date')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($incomings)
            ->addColumn('employee', function ($incoming) {
                if ($incoming->realization_id !== null && $incoming->realization && $incoming->realization->requestor) {
                    return $incoming->realization->requestor->name;
                }
                
                if ($incoming->realization_id !== null && !$incoming->realization) {
                    $realizationNo = $this->extractRealizationNoFromDescription($incoming->description);
                    if ($realizationNo) {
                        $realization = \App\Models\Realization::where('nomor', $realizationNo)->first();
                        if ($realization && $realization->requestor) {
                            return $realization->requestor->name;
                        }
                    }
                }
                
                return $incoming->cashier ? $incoming->cashier->name : '-';
            })
            ->addColumn('dept', function ($incoming) {
                if ($incoming->realization_id !== null && $incoming->realization && $incoming->realization->requestor && $incoming->realization->requestor->department) {
                    return $incoming->realization->requestor->department->akronim;
                }
                
                if ($incoming->realization_id !== null && !$incoming->realization) {
                    $realizationNo = $this->extractRealizationNoFromDescription($incoming->description);
                    if ($realizationNo) {
                        $realization = \App\Models\Realization::where('nomor', $realizationNo)->first();
                        if ($realization && $realization->requestor && $realization->requestor->department) {
                            return $realization->requestor->department->akronim;
                        }
                    }
                }
                
                return $incoming->cashier ? $incoming->cashier->name : '-';
            })
            ->addColumn('realization_no', function ($incoming) {
                if ($incoming->realization_id !== null && $incoming->realization) {
                    $payreqRemarks = $incoming->realization->payreq ? $incoming->realization->payreq->remarks : '';
                    return '<a href="#" style="color: black" title="' . $payreqRemarks . '">' . $incoming->realization->nomor . '</a>';
                } else {
                    return $incoming->description;
                }
            })
            ->editColumn('amount', function ($incoming) {
                return number_format($incoming->amount, 2);
            })
            ->addColumn('project', function ($incoming) {
                return $incoming->project ?? '-';
            })
            ->addColumn('account', function ($incoming) {
                return $incoming->account_id && $incoming->account ? $incoming->account->account_number . ' - ' . $incoming->account->account_name : '-';
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.incomings.action')
            ->rawColumns(['action', 'status', 'realization_no'])
            ->toJson();
    }

    public function received_data()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        
        // Start building the query
        $query = Incoming::with([
            'realization.requestor.department', 
            'realization.payreq', 
            'cashier', 
            'account'
        ])
        ->whereNotNull('receive_date');
        
        // Apply role-based filtering
        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            // No additional filters for admin
        } elseif (in_array('cashier', $userRoles)) {
            $query->whereIn('project', ['000H', 'APS']);
        } else {
            $query->where('project', auth()->user()->project);
        }
        
        // Order and prepare for datatables
        $query->orderBy('created_at', 'desc');
        
        return datatables()->of($query)
            ->addColumn('employee', function ($incoming) {
                return $incoming->realization_id !== null 
                    ? $incoming->realization->requestor->name 
                    : $incoming->cashier->name;
            })
            ->addColumn('dept', function ($incoming) {
                return $incoming->realization_id !== null 
                    ? $incoming->realization->requestor->department->akronim 
                    : $incoming->cashier->name;
            })
            ->addColumn('realization_no', function ($incoming) {
                if ($incoming->realization_id !== null) {
                    return '<a href="#" style="color: black" title="' . $incoming->realization->payreq->remarks . '">' . $incoming->realization->nomor . '</a>';
                } else {
                    return $incoming->description;
                }
            })
            ->editColumn('receive_date', function ($incoming) {
                return $incoming->receive_date ? date('d-M-Y', strtotime($incoming->receive_date)) : '-';
            })
            ->editColumn('amount', function ($incoming) {
                return number_format($incoming->amount, 2);
            })
            ->addColumn('account', function ($incoming) {
                return $incoming->account_id 
                    ? $incoming->account->account_number . ' - ' . $incoming->account->account_name 
                    : '-';
            })
            ->addColumn('status', function ($incoming) {
                if ($incoming->receive_date == null) {
                    return '<span class="badge badge-danger">NOT RECEIVE</span>';
                } else {
                    return '<span class="badge badge-success">RECEIVED</span>';
                }
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.incomings.received.action')
            ->rawColumns(['action', 'status', 'realization_no'])
            ->toJson();
    }

    private function extractRealizationNoFromDescription($description)
    {
        if (preg_match('/realization no\.?\s*(\d+)/i', $description, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
