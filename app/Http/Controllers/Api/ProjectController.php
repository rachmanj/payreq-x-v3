<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function get_projects()
    {
        $projects = \App\Models\Project::all(['id', 'code']); // specify the columns you need
        return response()->json($projects);
    }
}
