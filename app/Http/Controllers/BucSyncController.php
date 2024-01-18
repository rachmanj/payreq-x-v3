<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Rab;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class BucSyncController extends Controller
{
    public function index()
    {
        $rab_count = $this->get_rabs_count();
        $rab_local = Rab::count();

        return view('rabs.sync-bucs.index', [
            'rab_count' => $rab_count,
            'local_count' => $rab_local,
        ]);
    }

    public function get_rabs_count()
    {
        $url = env('URL_RABS');
        $client = new Client(['timeout' => 30]); // Set timeout to 2 minutes (120 seconds)
        try {
            $response = $client->get($url);
            $rabs_data = json_decode($response->getBody());

            return $rabs_data->rab_count;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return 'Error: Connection timeout. Please try again later.';
        }
    }

    public function sync_rabs()
    {
        $url = env('URL_RABS');
        $response = json_decode(file_get_contents($url));
        $rabs_data = $response->data;

        // truncate table rabs
        Rab::truncate();

        // insert rabs_data to table rabs
        foreach ($rabs_data as $rab) {
            Rab::create([
                'id' => $rab->id,
                'rab_no' => $rab->rab_no,
                'date' => $rab->date,
                'description' => $rab->description,
                'project_code' => $rab->project_code,
                'department_id' => $rab->department_id,
                'budget' => $rab->budget,
                'status' => $rab->status,
                'filename' => $rab->filename,
                'created_by' => $rab->created_by,
                'created_at' => $rab->created_at,
                'updated_at' => $rab->updated_at,
            ]);
        }

        return $this->index();
    }

    /**
     * Fungsi ini utk mengsingkronisasi realization_details di payreq-x dengan mengirimkan data 
     * realization_details ke payreq-x
     * field yand dikirim adalah realization_id, rab_id, amount, dan description
     */
    public function get_buc_payreqs()
    {
        $dnc_user = User::where('username', 'dncdiv')->first()->id;

        $send_data = RealizationDetail::select('realization_details.id as realization_detail_id', 'realization_details.rab_id', 'realization_details.amount', 'realization_details.description', 'realization_details.created_at', 'payreqs.nomor')
            ->join('realizations', 'realization_details.realization_id', '=', 'realizations.id')
            ->join('payreqs', 'realizations.payreq_id', '=', 'payreqs.id')
            ->where('realization_details.rab_id', '!=', null)
            ->whereHas('realization', function ($query) use ($dnc_user) {
                $query->whereHas('payreq', function ($query) use ($dnc_user) {
                    $query->where('user_id', $dnc_user);
                });
            })
            ->get();

        return [
            "realization_details" => $send_data,
        ];
    }

    /**
     * This is used to get Payreqs with no rab_id and belongs to dnc_user
     */
    public function getDncPayreqWithNoRabId()
    {
        $dnc_user = User::where('username', 'dncdiv')->first()->id;

        $sync_data = RealizationDetail::whereNull('rab_id')
            ->whereHas('realization', function ($query) use ($dnc_user) {
                $query->whereHas('payreq', function ($query) use ($dnc_user) {
                    $query->where('user_id', $dnc_user);
                });
            })
            ->get()
            ->map(function ($detail) {
                return [
                    'payreq_id' => $detail->realization->payreq->id,
                    'detail' => $detail,
                ];
            });

        return $sync_data;
    }

    /**
     * realization_details sebelum ditambahkan rab_id
     * Fungsi ini utk mengupdate rab_id pada realization_details dgn mengambil rab_id dari payreqs
     */
    public function update_rab()
    {
        // get payres with rab_id is not null and belongs to dnc_user 
        $dnc_user = User::where('username', 'dncdiv')->first()->id;

        // get Payreqs with user_id = dnc_user and rab_id is not null
        $payreqs = Payreq::where('user_id', $dnc_user)
            ->whereNotNull('rab_id')
            ->whereHas('realization')
            ->get();

        // get realizations of the payreqs
        $realizations = $payreqs->pluck('realization')->flatten();

        // get realization details of the realizations
        $realization_details = $realizations->pluck('realizationDetails')->flatten();

        // update rab_id of realization details with rab_id of payreqs
        foreach ($realization_details as $realization_detail) {
            // if realization is not null
            if ($realization_detail->realization && $realization_detail->realization->payreq) {
                $realization_detail->rab_id = $realization_detail->realization->payreq->rab_id;
                $realization_detail->save();
            }
        }

        return $realization_details;
    }

    /**
     * JUST TO TEST
     * Fungsi utk mengetes apakah rab_id dari payreqs dan realization_details sama
     */
    public function cek_rab_id()
    {
        // rab_id of payreqs
        $realization = Realization::where('id', 94)->first();

        $rab_id_payreq = $realization->payreq->rab_id;

        // rab_id of realization details
        $rab_id_realization_detail = RealizationDetail::where('id', 211)->first()
            ->rab_id;

        return [
            'rab_id_payreq' => $rab_id_payreq,
            'rab_id_realization_detail' => $rab_id_realization_detail
        ];
    }
}
