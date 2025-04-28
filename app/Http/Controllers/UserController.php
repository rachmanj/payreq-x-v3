<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('code', 'asc')->get();
        $departments = Department::orderBy('department_name', 'asc')->get();
        return view('users.index', compact(['projects', 'departments']));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $this->validateUserCreation($request);

        $user = new User();
        $this->updateUserBasicInfo($user, $request);
        $user->password = Hash::make($request->password);
        $user->save();
        $user->assignRole('user');

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $projects = Project::orderBy('code', 'asc')->get();
        $departments = Department::orderBy('department_name', 'asc')->get();
        $user = User::findOrFail($id);
        $roles = Role::all();
        $userRoles = $user->getRoleNames()->toArray();

        return view('users.edit', compact(['user', 'roles', 'userRoles', 'projects', 'departments']));
    }

    public function roles_user_update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        if ($request->password) {
            $this->validateUserUpdateWithPassword($request, $user);
            $user->password = Hash::make($request->password);
        } else {
            $this->validateUserUpdate($request, $user);
        }
        
        $this->updateUserBasicInfo($user, $request);
        $user->save();
        $user->syncRoles($request->role);
        
        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = 1;
        $user->save();

        return redirect()->route('users.index')->with('success', 'User activated successfully');
    }

    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = 0;
        $user->save();

        return redirect()->route('users.index')->with('success', 'User deactivated successfully');
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function change_password($id)
    {
        $user = User::findOrFail($id);
        return view('users.change-password', compact(['user']));
    }

    public function password_update(Request $request, $id)
    {
        $this->validatePasswordUpdate($request);
        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('dashboard.index')->with('success', 'User password updated successfully');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }

    public function getUserRoles()
    {
        $roles = User::find(auth()->user()->id)->getRoleNames()->toArray();
        return $roles;
    }

    public function data()
    {
        $users = User::orderBy('created_at', 'desc')->get();

        return datatables()->of($users)
            ->addIndexColumn()
            ->editColumn('is_active', function ($user) {
                if ($user->is_active == 1) {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-danger">Inactive</span>';
                }
            })
            ->addColumn('department', function ($user) {
                return $user->department->department_name;
            })
            ->addColumn('action', 'users.action')
            ->rawColumns(['action', 'is_active'])
            ->toJson();
    }
    
    private function validateUserCreation(Request $request)
    {
        $this->validate($request, [
            'name'          => 'required|min:3|max:255',
            'username'      => 'required|min:3|max:20|unique:users',
            'password'      => 'min:6',
            'password_confirmation' => 'required_with:password|same:password|min:6'
        ]);
    }
    
    private function validateUserUpdate(Request $request, User $user)
    {
        $this->validate($request, [
            'name'          => 'required|min:3|max:255',
            'username'      => 'required|min:3|max:50|unique:users,username,' . $user->id . ',id',
            'email'         => 'required|email|unique:users,email,' . $user->id . ',id',
        ]);
    }
    
    private function validateUserUpdateWithPassword(Request $request, User $user)
    {
        $this->validate($request, [
            'name'          => 'required|min:3|max:255',
            'username'      => 'required|min:3|max:50|unique:users,username,' . $user->id . ',id',
            'email'         => 'required|email|unique:users,email,' . $user->id . ',id',
            'password'      => 'min:6',
            'password_confirmation' => 'required_with:password|same:password|min:6'
        ]);
    }
    
    private function validatePasswordUpdate(Request $request)
    {
        $this->validate($request, [
            'password'      => 'required|min:5',
            'password_confirmation' => 'required_with:password|same:password|min:5'
        ]);
    }
    
    private function updateUserBasicInfo(User $user, Request $request)
    {
        $user->name = $request->name;
        $user->username = $request->username;
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('project')) {
            $user->project = $request->project;
        }
        if ($request->has('department_id')) {
            $user->department_id = $request->department_id;
        }
    }
}
