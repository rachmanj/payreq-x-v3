<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RealizationDetail;
use Illuminate\Support\Facades\DB;

class RealizationDetailController extends Controller
{
    /**
     * Returns sum of amounts for each type of a given unit_no
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sumByUnitNo(Request $request)
    {
        $request->validate([
            'unit_no' => 'required|string|max:20',
        ]);

        $unitNo = $request->input('unit_no');

        $sums = RealizationDetail::where('unit_no', $unitNo)
            ->whereNotNull('type')
            ->select('type', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->get();

        // Format the total_amount values
        $sums = $sums->map(function ($item) {
            $item->total_amount = number_format((float)$item->total_amount, 2, '.', ',');
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'unit_no' => $unitNo,
                'type_sums' => $sums
            ]
        ]);
    }
}
