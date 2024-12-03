<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return view('accounting.customers.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'project' => 'required|string|max:255',
            'sap_code' => 'required|string|max:255|unique:customers,code',
        ]);

        Customer::create([
            'name' => $request->name,
            'project' => $request->project,
            'code' => $request->sap_code,
            'type' => $request->type,
        ]);

        return redirect()->route('accounting.customers.index')->with('success', 'Customer created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'project' => 'required|string|max:255',
            'sap_code' => 'required|string|max:255|unique:customers,code,' . $id,
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update([
            'name' => $request->customer_name,
            'project' => $request->project,
            'code' => $request->sap_code,
            'type' => $request->type,
        ]);

        return redirect()->route('accounting.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('accounting.customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function data()
    {
        return datatables()->of(Customer::all())
            // add row index
            ->addIndexColumn()
            ->addColumn('action', 'accounting.customers.action')
            ->toJson();
    }
}
