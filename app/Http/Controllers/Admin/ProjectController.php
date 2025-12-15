<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Sap\SapProjectSyncService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function __construct(
        protected SapProjectSyncService $syncService
    ) {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable();
        }

        return view('admin.projects.index');
    }

    protected function dataTable()
    {
        $projects = Project::query();

        return DataTables::of($projects)
            ->addColumn('actions', function ($project) {
                $actions = '';
                
                if (auth()->user()->can('projects.manage-visibility')) {
                    $visibilityClass = $project->is_selectable ? 'btn-warning' : 'btn-success';
                    $visibilityIcon = $project->is_selectable ? 'fa-eye-slash' : 'fa-eye';
                    $visibilityTitle = $project->is_selectable ? 'Hide from selections' : 'Show in selections';
                    
                    $actions .= '<button class="btn btn-sm ' . $visibilityClass . ' toggle-visibility-btn" 
                        data-id="' . $project->id . '" 
                        data-current="' . ($project->is_selectable ? '1' : '0') . '"
                        title="' . $visibilityTitle . '">
                        <i class="fas ' . $visibilityIcon . '"></i>
                    </button> ';
                }
                
                return $actions;
            })
            ->editColumn('is_active', function ($project) {
                return $project->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->editColumn('is_selectable', function ($project) {
                return $project->is_selectable
                    ? '<span class="badge badge-primary">Visible</span>'
                    : '<span class="badge badge-dark">Hidden</span>';
            })
            ->editColumn('synced_at', function ($project) {
                if (!$project->synced_at) {
                    return '<span class="text-muted">Never</span>';
                }

                return \Illuminate\Support\Carbon::parse($project->synced_at)->format('Y-m-d H:i:s');
            })
            ->rawColumns(['is_active', 'is_selectable', 'synced_at', 'actions'])
            ->make(true);
    }

    public function syncFromSap(Request $request)
    {
        try {
            $result = $this->syncService->syncProjects();

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('admin.projects.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('admin.projects.index')
                    ->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.projects.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function toggleVisibility(Project $project)
    {
        try {
            $project->update([
                'is_selectable' => !$project->is_selectable,
            ]);

            $message = $project->is_selectable
                ? 'Project is now visible in selections.'
                : 'Project has been hidden from selections.';

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $project->only(['id', 'is_selectable']),
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

