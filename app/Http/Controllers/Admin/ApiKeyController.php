<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of API keys
     */
    public function index()
    {
        return view('admin.api-keys.index');
    }

    /**
     * DataTables data source for API keys
     */
    public function data()
    {
        $apiKeys = ApiKey::with('creator')->select('api_keys.*');

        return DataTables::of($apiKeys)
            ->addColumn('creator_name', function ($apiKey) {
                return $apiKey->creator->name ?? 'N/A';
            })
            ->addColumn('status_badge', function ($apiKey) {
                if ($apiKey->is_active) {
                    return '<span class="badge badge-success">Active</span>';
                }
                return '<span class="badge badge-danger">Inactive</span>';
            })
            ->addColumn('last_used', function ($apiKey) {
                if ($apiKey->last_used_at) {
                    return $apiKey->last_used_at->diffForHumans();
                }
                return '<span class="text-muted">Never used</span>';
            })
            ->addColumn('action', function ($apiKey) {
                $actions = '';

                if ($apiKey->is_active) {
                    $actions .= '<button class="btn btn-sm btn-warning deactivate-btn" data-id="' . $apiKey->id . '" title="Deactivate">
                        <i class="fas fa-ban"></i>
                    </button> ';
                } else {
                    $actions .= '<button class="btn btn-sm btn-success activate-btn" data-id="' . $apiKey->id . '" title="Activate">
                        <i class="fas fa-check"></i>
                    </button> ';
                }

                $actions .= '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $apiKey->id . '" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>';

                return $actions;
            })
            ->rawColumns(['status_badge', 'last_used', 'action'])
            ->make(true);
    }

    /**
     * Store a newly created API key
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'application' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $result = ApiKey::generate(
            $request->name,
            $request->application,
            $request->description,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'API key generated successfully. Please copy the key now, it will not be shown again.',
            'data' => [
                'raw_key' => $result['raw_key'],
                'api_key_id' => $result['api_key']->id,
                'name' => $result['api_key']->name,
            ],
        ], 201);
    }

    /**
     * Activate an API key
     */
    public function activate($id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->activate();

        return response()->json([
            'success' => true,
            'message' => 'API key activated successfully',
        ]);
    }

    /**
     * Deactivate an API key
     */
    public function deactivate($id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'API key deactivated successfully',
        ]);
    }

    /**
     * Remove the specified API key
     */
    public function destroy($id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key deleted successfully',
        ]);
    }
}
