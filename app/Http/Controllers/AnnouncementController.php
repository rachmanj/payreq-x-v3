<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AnnouncementController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(['auth', 'role:superadmin']);
    // }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $announcements = Announcement::with('creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($announcement) {
                $announcement->target_roles = collect($announcement->target_roles)->sort()->values()->all();
                return $announcement;
            });

        return view('announcements.index', compact('announcements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::orderBy('name', 'asc')->pluck('name', 'name');
        return view('announcements.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:65535',
            'start_date' => 'required|date|after_or_equal:today',
            'duration_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
            'target_roles' => 'required|array|min:1',
            'target_roles.*' => 'exists:roles,name',
        ], [
            'content.required' => 'Announcement content is required',
            'content.max' => 'Announcement content is too long',
            'start_date.required' => 'Start date is required',
            'start_date.after_or_equal' => 'Start date cannot be before today',
            'duration_days.required' => 'Duration days is required',
            'duration_days.min' => 'Duration must be at least 1 day',
            'duration_days.max' => 'Duration cannot exceed 365 days',
            'status.required' => 'Status must be selected',
            'status.in' => 'Status must be active or inactive',
            'target_roles.required' => 'At least one target role must be selected',
            'target_roles.min' => 'At least one target role must be selected',
            'target_roles.*.exists' => 'Selected role does not exist',
        ]);

        $validated['created_by'] = Auth::id();
        Announcement::create($validated);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        return view('announcements.show', compact('announcement'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Announcement $announcement)
    {
        $roles = Role::orderBy('name', 'asc')->pluck('name', 'name');
        return view('announcements.edit', compact('announcement', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:65535',
            'start_date' => 'required|date',
            'duration_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
            'target_roles' => 'required|array|min:1',
            'target_roles.*' => 'exists:roles,name',
        ]);

        $announcement->update($validated);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully');
    }

    public function toggleStatus(Announcement $announcement)
    {
        $announcement->update([
            'status' => $announcement->status === 'active' ? 'inactive' : 'active'
        ]);

        $status = $announcement->status === 'active' ? 'activated' : 'deactivated';

        return redirect()->route('announcements.index')
            ->with('success', "Announcement {$status} successfully");
    }
}
