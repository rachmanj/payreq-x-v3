<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentSyncController extends Controller
{
    public function index()
    {
        $url = env('URL_EQUIPMENTS');
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $data = json_decode($response->getBody()->getContents(), true);

        $api_count = $data['count'];
        $local_count = Equipment::count();

        return view('equipments.sync.index', [
            'api_count' => $api_count,
            'local_count' => $local_count,
        ]);
    }

    public function sync_equipments()
    {
        $url = env('URL_EQUIPMENTS');
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $data = json_decode($response->getBody()->getContents(), true);
        $equipments_data = $data['data'];

        if (count($equipments_data) == 0) {
            return redirect()->route('equipments.sync.index')->with('error', 'No data to sync.');
        }

        // truncate table equipments
        Equipment::truncate();

        // insert equipments_data to table equipments
        foreach ($equipments_data as $equipment) {
            Equipment::create([
                'unit_code' => $equipment['unit_code'],
                'project' => $equipment['project'],
                'plant_group' => $equipment['plant_group'],
                'model' => $equipment['model'],
                'nomor_polisi' => $equipment['nomor_polisi'],
            ]);
        }

        return redirect()->route('equipments.sync.index')->with('success', 'Sync data success.');
    }
}
