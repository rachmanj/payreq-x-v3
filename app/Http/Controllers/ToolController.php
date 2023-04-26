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

    public function getEquipments($project = null)
    {
        $url = env('URL_EQUIPMENTS');

        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $equipments = json_decode($response->getBody()->getContents(), true)['data'];

        if ($project) {
            $equipments = array_filter($equipments, function ($item) use ($project) {
                return $item['project'] == $project;
            });
        }

        return $equipments;
    }

    public function getLastTransaction($tyre_id)
    {
        $last_transaction = Transaction::where('tyre_id', $tyre_id)->orderBy('id', 'desc')->first();

        if ($last_transaction) {
            return $last_transaction;
        } else {
            return null;
        }
    }

    public function getFirstTransaction($tyre_id)
    {
        $first_transaction = Transaction::where('tyre_id', $tyre_id)->orderBy('id', 'asc')->first();

        if ($first_transaction) {
            return $first_transaction;
        } else {
            return null;
        }
    }

    public function getHMTyre($tyre_id)
    {
        $first_transaction = $this->getFirstTransaction($tyre_id);
        $last_transaction = $this->getLastTransaction($tyre_id);

        if ($first_transaction && $last_transaction) {
            $first_transaction_date = $first_transaction->hm;
            $last_transaction_date = $last_transaction->hm;

            $diff = $last_transaction_date - $first_transaction_date;

            return $diff;
        } else {
            return null;
        }
    }

    public function progress($rab_id)
    {
        $rab = Rab::find($rab_id);
        // $payreqs = Payreq::where('rab_id', $rab_id)->get();
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
