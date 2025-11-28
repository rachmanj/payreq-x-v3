<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentSyncController extends Controller
{
    public function index()
    {
        $url = env('URL_EQUIPMENTS') . '?status=ACTIVE';
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->request('GET', $url, [
                'timeout' => 10,
                'http_errors' => true
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                throw new \Exception("API returned status code: {$statusCode}");
            }
            
            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response from API: " . json_last_error_msg());
            }

            $equipments_data = $data['data'] ?? [];
            
            // Use actual count of items in data array for accuracy
            // This ensures we show the exact number of items that will be synced
            $api_count = count($equipments_data);
            
            // If data array is empty but API count exists, use API count
            // This handles cases where API might return count but empty data array
            if ($api_count == 0 && isset($data['count']) && $data['count'] > 0) {
                $api_count = $data['count'];
            }
            
            $local_count = Equipment::count();

            return view('equipments.sync.index', [
                'api_count' => $api_count,
                'local_count' => $local_count,
            ]);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $errorMessage = "ARK-Fleet API server error. Please check if the ARK-Fleet server is running and accessible.";
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= " (HTTP {$statusCode})";
            }
            
            return view('equipments.sync.index', [
                'api_count' => 0,
                'local_count' => Equipment::count(),
            ])->with('error', $errorMessage);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorMessage = "ARK-Fleet API client error. Please check the API endpoint configuration.";
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= " (HTTP {$statusCode})";
            }
            
            return view('equipments.sync.index', [
                'api_count' => 0,
                'local_count' => Equipment::count(),
            ])->with('error', $errorMessage);
        } catch (\Exception $e) {
            return view('equipments.sync.index', [
                'api_count' => 0,
                'local_count' => Equipment::count(),
            ])->with('error', 'Failed to fetch equipment data from ARK-Fleet API: ' . $e->getMessage());
        }
    }

    public function sync_equipments()
    {
        $url = env('URL_EQUIPMENTS') . '?status=ACTIVE';
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->request('GET', $url);
            $data = json_decode($response->getBody()->getContents(), true);
            $equipments_data = $data['data'] ?? [];

            if (count($equipments_data) == 0) {
                return redirect()->route('equipments.sync.index')->with('error', 'No active equipment data to sync.');
            }

            // truncate table equipments
            Equipment::truncate();

            // insert equipments_data to table equipments
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
        } catch (\Exception $e) {
            return redirect()->route('equipments.sync.index')->with('error', 'Failed to sync equipment data: ' . $e->getMessage());
        }
    }
}
