<?php

namespace App\Http\Controllers\UserPayreq;

use App\Models\Payreq;
use App\Models\LotClaim;
use App\Services\LotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LotClaimController extends Controller
{
    protected $lotService;

    public function __construct(LotService $lotService)
    {
        $this->lotService = $lotService;
    }

    public function index()
    {
        return view('user-payreqs.lotclaims.index');
    }

    public function data()
    {
        $lotClaims = LotClaim::with(['user'])->where('user_id', auth()->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables()->of($lotClaims)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '<a href="' . route('user-payreqs.lotclaims.show', $row->id) . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Detail</a>';
            })
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('user-payreqs.lotclaims.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'lot_no' => 'required|unique:lot_claims,lot_no',
            // ... other validations
        ]);

        DB::beginTransaction();
        try {
            // Prepare main lot claim data
            $lotClaimData = $request->only([
                'user_id',
                'lot_no',
                'claim_date',
                'project',
                'advance_amount',
                'claim_remarks',
                'accommodation_total',
                'travel_total',
                'meal_total',
                'total_claim',
                'difference',
                'is_claimed'
            ]);

            // Convert all amount values from string to numeric
            $amountFields = [
                'advance_amount',
                'accommodation_total',
                'travel_total',
                'meal_total',
                'total_claim',
                'difference'
            ];

            foreach ($amountFields as $field) {
                if (isset($lotClaimData[$field])) {
                    $lotClaimData[$field] = str_replace(',', '', $lotClaimData[$field]);
                }
            }

            // Create lot claim
            $lotClaim = LotClaim::create($lotClaimData);

            // Store accommodations
            if ($request->has('accommodations')) {
                foreach ($request->accommodations as $accommodation) {
                    if (!empty($accommodation['description']) || !empty($accommodation['accommodation_amount'])) {
                        // Convert amount from string to numeric
                        if (isset($accommodation['accommodation_amount'])) {
                            $accommodation['accommodation_amount'] = str_replace(',', '', $accommodation['accommodation_amount']);
                        }
                        $lotClaim->accommodations()->create($accommodation);
                    }
                }
            }

            // Store travels
            if ($request->has('travels')) {
                foreach ($request->travels as $travel) {
                    if (!empty($travel['description']) || !empty($travel['travel_amount'])) {
                        // Convert amount from string to numeric
                        if (isset($travel['travel_amount'])) {
                            $travel['travel_amount'] = str_replace(',', '', $travel['travel_amount']);
                        }
                        $lotClaim->travels()->create($travel);
                    }
                }
            }

            // Store meals
            if ($request->has('meals')) {
                foreach ($request->meals as $meal) {
                    if (!empty($meal['meal_type']) || !empty($meal['people_count'])) {
                        // Convert amounts from string to numeric
                        if (isset($meal['per_person_limit'])) {
                            $meal['per_person_limit'] = str_replace(',', '', $meal['per_person_limit']);
                        }
                        $lotClaim->meals()->create($meal);
                    }
                }
            }

            DB::commit();
            return redirect()->route('user-payreqs.lotclaims.index')
                ->with('success', 'LOT Claim created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create LOT Claim: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LotClaim $lotclaim)
    {
        $lotclaim->load(['user', 'accommodations', 'travels', 'meals']);

        // Fetch LOT detail from API
        $lotDetail = Http::post(config('services.lot.base_url') . config('services.lot.search_endpoint'), [
            'travel_number' => $lotclaim->lot_no
        ])->json();
        return view('user-payreqs.lotclaims.show', compact('lotclaim', 'lotDetail'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LotClaim $lotclaim)
    {
        $lotclaim->load(['user', 'accommodations', 'travels', 'meals']);

        // Fetch LOT detail from API
        // $lotDetail = Http::post(config('services.lot.base_url') . config('services.lot.search_endpoint'), [
        //     'travel_number' => $lotclaim->lot_no
        // ])->json();

        return view('user-payreqs.lotclaims.edit', compact('lotclaim'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LotClaim $lotclaim)
    {
        $request->validate([
            'lot_no' => [
                'required',
                function ($attribute, $value, $fail) use ($lotclaim) {
                    if ($value !== $lotclaim->lot_no) {
                        $exists = LotClaim::where('lot_no', $value)
                            ->where('id', '!=', $lotclaim->id)
                            ->exists();

                        if ($exists) {
                            $fail('This LOT number has already been taken.');
                        }
                    }
                },
            ],
            // ... other validations
        ]);

        DB::beginTransaction();
        try {
            // Prepare main lot claim data
            $lotClaimData = $request->only([
                'claim_date',
                'advance_amount',
                'claim_remarks',
                'accommodation_total',
                'travel_total',
                'meal_total',
                'total_claim',
                'difference'
            ]);

            // Convert all amount values from string to numeric (handle US format)
            $amountFields = [
                'advance_amount',
                'accommodation_total',
                'travel_total',
                'meal_total',
                'total_claim',
                'difference'
            ];

            foreach ($amountFields as $field) {
                if (isset($lotClaimData[$field])) {
                    // Remove commas (thousand separators) but keep decimal point
                    $lotClaimData[$field] = str_replace(',', '', $lotClaimData[$field]);
                }
            }

            // Update lot claim
            $lotclaim->update($lotClaimData);

            // Handle accommodations
            if ($request->has('accommodations')) {
                // Delete removed accommodations
                $keepIds = collect($request->accommodations)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                $lotclaim->accommodations()
                    ->whereNotIn('id', $keepIds)
                    ->delete();

                // Update or create accommodations
                foreach ($request->accommodations as $accommodation) {
                    if (!empty($accommodation['id'])) {
                        // Convert amount from string to numeric (handle US format)
                        if (isset($accommodation['accommodation_amount'])) {
                            $accommodation['accommodation_amount'] = str_replace(',', '', $accommodation['accommodation_amount']);
                        }
                        $lotclaim->accommodations()
                            ->find($accommodation['id'])
                            ->update($accommodation);
                    } else {
                        // Convert amount from string to numeric (handle US format)
                        if (isset($accommodation['accommodation_amount'])) {
                            $accommodation['accommodation_amount'] = str_replace(',', '', $accommodation['accommodation_amount']);
                        }
                        $lotclaim->accommodations()->create($accommodation);
                    }
                }
            }

            // Handle travels
            if ($request->has('travels')) {
                // Delete removed travels
                $keepIds = collect($request->travels)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                $lotclaim->travels()
                    ->whereNotIn('id', $keepIds)
                    ->delete();

                // Update or create travels
                foreach ($request->travels as $travel) {
                    if (!empty($travel['id'])) {
                        // Convert amount from string to numeric (handle US format)
                        if (isset($travel['travel_amount'])) {
                            $travel['travel_amount'] = str_replace(',', '', $travel['travel_amount']);
                        }
                        $lotclaim->travels()
                            ->find($travel['id'])
                            ->update($travel);
                    } else {
                        // Convert amount from string to numeric (handle US format)
                        if (isset($travel['travel_amount'])) {
                            $travel['travel_amount'] = str_replace(',', '', $travel['travel_amount']);
                        }
                        $lotclaim->travels()->create($travel);
                    }
                }
            }

            // Handle meals
            if ($request->has('meals')) {
                // Delete removed meals
                $keepIds = collect($request->meals)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                $lotclaim->meals()
                    ->whereNotIn('id', $keepIds)
                    ->delete();

                // Update or create meals
                foreach ($request->meals as $meal) {
                    if (!empty($meal['id'])) {
                        // Convert amounts from string to numeric (handle US format)
                        if (isset($meal['per_person_limit'])) {
                            $meal['per_person_limit'] = str_replace(',', '', $meal['per_person_limit']);
                        }
                        $lotclaim->meals()
                            ->find($meal['id'])
                            ->update($meal);
                    } else {
                        // Convert amounts from string to numeric (handle US format)
                        if (isset($meal['per_person_limit'])) {
                            $meal['per_person_limit'] = str_replace(',', '', $meal['per_person_limit']);
                        }
                        $lotclaim->meals()->create($meal);
                    }
                }
            }

            DB::commit();
            return redirect()->route('user-payreqs.lotclaims.show', $lotclaim)
                ->with('success', 'LOT Claim updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update LOT Claim: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LotClaim $lotclaim)
    {
        DB::beginTransaction();
        try {
            // Delete all related records first
            $lotclaim->accommodations()->delete();
            $lotclaim->travels()->delete();
            $lotclaim->meals()->delete();

            // Then delete the main record
            $lotclaim->delete();

            DB::commit();
            return redirect()->route('user-payreqs.lotclaims.index')
                ->with('success', 'LOT Claim deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete LOT Claim: ' . $e->getMessage());
        }
    }

    public function print(LotClaim $lotclaim)
    {
        $lotclaim->load(['user', 'accommodations', 'travels', 'meals']);
        return view('user-payreqs.lotclaims.print', compact('lotclaim'));
    }

    public function searchLOT(Request $request)
    {
        $searchParams = [
            'travel_number' => $request->travel_number,
            'traveler' => $request->traveler,
            'department' => $request->department,
            'project' => $request->project
        ];

        $result = $this->lotService->search($searchParams, true);

        // If LOT data is found and has travel_number, check for related payreq
        if ($result['success'] && !empty($result['data'])) {
            foreach ($result['data'] as &$lot) {
                if (!empty($lot['official_travel_number'])) {
                    // Find related payreq
                    $payreq = Payreq::where('lot_no', $lot['official_travel_number'])
                        ->select('id', 'nomor', 'amount', 'status')
                        ->first();

                    if ($payreq) {
                        $lot['payment_request'] = [
                            'id' => $payreq->id,
                            'nomor' => $payreq->nomor,
                            'amount' => $payreq->amount,
                            'status' => $payreq->status
                        ];
                    } else {
                        $lot['payment_request'] = [
                            'id' => null,
                            'nomor' => null,
                            'amount' => null,
                            'status' => 'No payment request found for this LOT number'
                        ];
                    }
                }
            }
        }

        return response()->json($result);
    }
}
