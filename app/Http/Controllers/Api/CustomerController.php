<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function get_customers()
    {
        $customers = Customer::select(['id', 'code', 'name', 'type', 'project'])->get();
        $customerCount = Customer::where('type', 'customer')->count();
        $vendorCount = Customer::where('type', 'vendor')->count();

        return response()->json([
            'customer_count' => $customerCount,
            'vendor_count' => $vendorCount,
            'customers' => $customers
        ]);
    }
}
