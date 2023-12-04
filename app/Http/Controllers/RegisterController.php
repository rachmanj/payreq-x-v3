<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('code', 'asc')->get();
        $departments = Department::orderBy('department_name', 'asc')->get();

        return view('register.index', compact(['projects', 'departments']));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'          => 'required|min:3|max:255',
            'username'      => 'required|min:3|max:20|unique:users',
            'password'      => 'min:6',
            'password_confirmation' => 'required_with:password|same:password|min:6',
            'project'           => 'required',
            'department_id'     => 'required',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->password = Hash::make($request->password);
        $user->project = $request->project;
        $user->department_id = $request->department_id;
        $user->save();
        $user->assignRole('user');


        return redirect()->route('login')->with('success', 'User created successfully');
    }
}
