<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Services\ArkFleetService;
use Illuminate\Http\JsonResponse;

class EquipmentSyncController extends Controller
{
    public function __construct(private readonly ArkFleetService $arkFleetService) {}

    public function index()
    {
        return view('equipments.sync.index', [
            'api_count' => null,
            'local_count' => Equipment::count(),
            'configured_url' => $this->arkFleetService->getConfiguredBaseUrl(),
            'request_url' => $this->arkFleetService->buildActiveEquipmentsUrl(),
        ]);
    }

    public function fetchCount(): JsonResponse
    {
        $result = $this->arkFleetService->fetchActiveEquipmentCount();

        return response()->json($result);
    }

    public function sync_equipments()
    {
        $result = $this->arkFleetService->fetchActiveEquipmentsData();

        if (! $result['success']) {
            return redirect()->route('equipments.sync.index')->with('error', $result['message']);
        }

        $equipments_data = $result['data'] ?? [];

        if (count($equipments_data) === 0) {
            return redirect()->route('equipments.sync.index')->with('error', 'No active equipment data to sync.');
        }

        Equipment::truncate();

        foreach ($equipments_data as $equipment) {
            Equipment::create([
                'unit_code' => $equipment['unit_no'] ?? null,
                'project' => $equipment['project_code'] ?? null,
                'plant_group' => $equipment['plant_group'] ?? null,
                'model' => $equipment['model'] ?? null,
                'nomor_polisi' => $equipment['nomor_polisi'] ?? null,
            ]);
        }

        return redirect()->route('equipments.sync.index')->with('success', 'Active equipments synchronized successfully.');
    }
}
