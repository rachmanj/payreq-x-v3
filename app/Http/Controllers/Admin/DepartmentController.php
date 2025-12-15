<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\Sap\SapDepartmentSyncService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    public function __construct(
        protected SapDepartmentSyncService $syncService
    ) {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable();
        }

        return view('admin.departments.index');
    }

    protected function dataTable()
    {
        $departments = Department::query()->with('parent');

        return DataTables::of($departments)
            ->addColumn('actions', function ($department) {
                $actions = '';
                
                if (auth()->user()->can('departments.manage-visibility')) {
                    $visibilityClass = $department->is_selectable ? 'btn-warning' : 'btn-success';
                    $visibilityIcon = $department->is_selectable ? 'fa-eye-slash' : 'fa-eye';
                    $visibilityTitle = $department->is_selectable ? 'Hide from selections' : 'Show in selections';
                    
                    $actions .= '<button class="btn btn-sm ' . $visibilityClass . ' toggle-visibility-btn" 
                        data-id="' . $department->id . '" 
                        data-current="' . ($department->is_selectable ? '1' : '0') . '"
                        title="' . $visibilityTitle . '">
                        <i class="fas ' . $visibilityIcon . '"></i>
                    </button> ';
                }
                
                return $actions;
            })
            ->editColumn('is_active', function ($department) {
                return $department->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->editColumn('is_selectable', function ($department) {
                return $department->is_selectable
                    ? '<span class="badge badge-primary">Visible</span>'
                    : '<span class="badge badge-dark">Hidden</span>';
            })
            ->editColumn('parent', function ($department) {
                return $department->parent
                    ? $department->parent->department_name
                    : '<span class="text-muted">-</span>';
            })
            ->editColumn('synced_at', function ($department) {
                if (!$department->synced_at) {
                    return '<span class="text-muted">Never</span>';
                }

                return \Illuminate\Support\Carbon::parse($department->synced_at)->format('Y-m-d H:i:s');
            })
            ->rawColumns(['is_active', 'is_selectable', 'parent', 'synced_at', 'actions'])
            ->make(true);
    }

    public function syncFromSap(Request $request)
    {
        try {
            $result = $this->syncService->syncDepartments();

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('admin.departments.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('admin.departments.index')
                    ->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.departments.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function toggleVisibility(Department $department)
    {
        try {
            $department->update([
                'is_selectable' => !$department->is_selectable,
            ]);

            $message = $department->is_selectable
                ? 'Department is now visible in selections.'
                : 'Department has been hidden from selections.';

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $department->only(['id', 'is_selectable']),
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}

