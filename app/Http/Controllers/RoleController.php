<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }


    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name]);

        $role->givePermissionTo($request->input('permission'));

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $role               = Role::find($id);
        $permissions        = Permission::all();
        $rolePermissions    = $role->permissions()->get()->pluck('id')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }


    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        $this->validate($request, [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $id],
        ]);

        $role->update(['name' => $request->name]);

        $role->syncPermissions($request->input('permission'));

        return redirect()->route('roles.index')->with('success', 'Role successfully updated!');
    }


    public function destroy($id)
    {
        //
    }

    public function data()
    {
        $roles = Role::all();

        return datatables()->of($roles)
            ->addColumn('action', 'roles.action')
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->toJson();
    }
}
