<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Rab;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\PayreqController;
use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class AccountingPayreqController extends Controller
{
    protected $payreqController;
    protected $approvalPlanController;
    protected $userController;
    protected $cacheTime = 300; // 5 minutes cache

    public function __construct(
        PayreqController $payreqController,
        ApprovalPlanController $approvalPlanController,
        UserController $userController
    ) {
        $this->payreqController = $payreqController;
        $this->approvalPlanController = $approvalPlanController;
        $this->userController = $userController;
    }

    public function index()
    {
        return view('accounting.payreqs.index');
    }

    public function create()
    {
        // Optimize queries by selecting only needed fields and using caching
        $employees = Cache::remember('employees_list', $this->cacheTime, function () {
            return User::select('id', 'name')->orderBy('name')->get();
        });
        
        $rabs = Cache::remember('active_rabs', $this->cacheTime, function () {
            return Rab::select('id', 'rab_no')
                ->where('status', 'progress')
                ->orderBy('rab_no', 'asc')
                ->get();
        });
        
        $payreq_no = $this->payreqController->generateDraftNumber();

        return view('accounting.payreqs.create', compact([
            'employees',
            'rabs',
            'payreq_no',
        ]));
    }

    public function store(Request $request)
    {
        // Validate request data
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            // Add other validation rules as needed
        ]);

        // Optimize by fetching user data in one query
        $user = User::select('id', 'project', 'department_id')
            ->findOrFail($request->employee_id);
        
        $request['project'] = $user->project;
        $request['department_id'] = $user->department_id;

        $response = $this->payreqController->store($request);

        if ($response->status == 'draft') {
            return redirect()->route('user-payreqs.index')
                ->with('success', 'Payreq Advance Draft saved');
        }
        
        $approval_plan_response = $this->approvalPlanController
            ->create_approval_plan('payreq', $response->id);

        if ($approval_plan_response == false) {
            // Update payreq status to draft
            Payreq::findOrFail($response->id)->update([
                'status' => 'draft',
                'editable' => '1',
                'deletable' => '1',
            ]);
            
            // Clear cache after update
            $this->clearPayreqCache();
            
            return redirect()->route('accounting.payreqs.index')
                ->with('error', 'No Approval Plan found. Payreq Advance saved as draft, contact IT Department');
        }

        // Clear cache after successful submission
        $this->clearPayreqCache();
        
        return redirect()->route('accounting.payreqs.index')
            ->with('success', 'Payreq Advance submitted');
    }

    public function show($payreq_id)
    {
        // Use cache for individual payreq data
        $payreq = Cache::remember('payreq_' . $payreq_id, $this->cacheTime, function () use ($payreq_id) {
            return Payreq::with([
                'realization:id,payreq_id,nomor', 
                'realization.realizationDetails'
            ])
            ->findOrFail($payreq_id);
        });

        return view('accounting.payreqs.show', compact('payreq'));
    }

    public function data(Request $request)
    {
        try {
            // Get parameters from DataTables
            $start = (int)$request->input('start', 0);
            $length = (int)$request->input('length', 10);
            $draw = (int)$request->input('draw', 1);
            $search = $request->input('search.value', '');
            
            // Handle order column safely
            $orderColumn = 5; // Default to created_at
            $orderDir = 'desc';
            
            if ($request->has('order') && is_array($request->input('order'))) {
                $orderColumn = (int)$request->input('order.0.column', 5);
                $orderDir = $request->input('order.0.dir', 'desc');
            }
            
            // Map DataTables column index to actual column names
            $columns = [
                0 => 'id',
                1 => 'employee_id', // Will be handled separately
                2 => 'project',
                3 => 'nomor',
                4 => 'realization_id', // Will be handled separately
                5 => 'created_at',
                6 => 'type',
                7 => 'status',
                8 => 'amount',
            ];
            
            // Get column to order by
            $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'created_at';
            
            // Use simpler caching strategy
            $userRoles = $this->userController->getUserRoles();
            
            // Start building the query
            $query = Payreq::query();
            
            // Apply role-based filtering
            if (!array_intersect(['superadmin', 'admin'], $userRoles)) {
                if (in_array('cashier', $userRoles)) {
                    $query->whereIn('project', ['000H', 'APS']);
                } else {
                    $query->where('project', Auth::user()->project);
                }
            }
            
            // Apply search if provided
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('nomor', 'like', "%{$search}%")
                      ->orWhere('project', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%");
                });
            }
            
            // Count total records (for pagination)
            $recordsTotal = $query->count();
            $recordsFiltered = $recordsTotal;
            
            // Apply ordering
            if ($orderColumnName === 'employee_id') {
                $query->join('users', 'payreqs.employee_id', '=', 'users.id')
                      ->select('payreqs.*')
                      ->orderBy('users.name', $orderDir);
            } else {
                $query->orderBy($orderColumnName, $orderDir);
            }
            
            // Apply pagination and eager load relationships
            $payreqs = $query->with(['requestor:id,name', 'realization:id,payreq_id,nomor'])
                ->offset($start)
                ->limit($length)
                ->get();
            
            // Format data for DataTables
            $data = [];
            foreach ($payreqs as $index => $payreq) {
                $actionHtml = '<a href="' . route('accounting.payreqs.show', $payreq->id) . '" class="btn btn-xs btn-success">show</a>';
                
                $data[] = [
                    'DT_RowIndex' => $start + $index + 1,
                    'employee' => $payreq->requestor ? $payreq->requestor->name : 'Unknown',
                    'project' => $payreq->project,
                    'nomor' => $payreq->nomor,
                    'realization_no' => $payreq->realization ? $payreq->realization->nomor : 'n/a',
                    'created_at' => Carbon::parse($payreq->created_at)->format('d-M-Y'),
                    'type' => $payreq->type,
                    'status' => $payreq->status,
                    'amount' => number_format($payreq->amount, 2),
                    'action' => $actionHtml
                ];
            }
            
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in payreqs data: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data'
            ], 500);
        }
    }
    
    /**
     * Clear payreq-related cache
     */
    protected function clearPayreqCache()
    {
        Cache::forget('employees_list');
        Cache::forget('active_rabs');
        
        // Clear individual payreq caches
        $payreqs = Payreq::select('id')->get();
        foreach ($payreqs as $payreq) {
            Cache::forget('payreq_' . $payreq->id);
        }
    }
}
