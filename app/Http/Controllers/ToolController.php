<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Rab;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function getProjects()
    {
        $url = env('URL_PROJECTS');
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $projects = json_decode($response->getBody()->getContents(), true)['data'];

        return $projects;
    }

    public function progress($rab_id)
    {
        $rab = Rab::find($rab_id);
        $payreqs = $rab->payreqs;
        $total_advance = $payreqs->whereNotNull('outgoing_date')->whereNull('realization_date')->sum('payreq_idr');
        $total_realization = $payreqs->whereNotNull('realization_date')->sum('realization_amount');
        $total_release = $total_advance + $total_realization;
        $progress = ($total_release / $rab->budget) * 100;

        return $progress;
    }

    public function statusColor($progress)
    {
        if ($progress == 100) {
            return 'bg-success';
        } elseif ($progress > 0 && $progress < 100) {
            return 'bg-warning';
        } else {
            return 'bg-danger';
        }
    }
}
