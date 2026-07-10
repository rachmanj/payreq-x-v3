<?php

namespace App\Http\Controllers;

class EquipmentController extends Controller
{
    public function index()
    {
        return view('equipments.index');
    }

    // data
    public function data()
    {
        $url = config('services.ark_fleet.url_equipments');

        $client = new \GuzzleHttp\Client;
        $response = $client->request('GET', $url);
        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // filter data only for certain project
        // $data = array_filter($data, function ($item) {
        //     return $item['project'] == '011C';
        // });

        return datatables()->of($data)
            ->addIndexColumn()
            ->toJson();
    }
}
