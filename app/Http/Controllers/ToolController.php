<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\CashJournal;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use Carbon\Carbon;

class ToolController extends Controller
{
    public function getApiProjects() // api call to arkFleet
    {
        $url = env('URL_PROJECTS');
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $projects = json_decode($response->getBody()->getContents(), true)['data'];

        return $projects;
    }

    function penyebut($nilai)
    {
        $nilai = abs($nilai);
        $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " " . $huruf[$nilai];
        } else if ($nilai < 20) {
            $temp = $this->penyebut($nilai - 10) . " belas";
        } else if ($nilai < 100) {
            $temp = $this->penyebut($nilai / 10) . " puluh" . $this->penyebut($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " seratus" . $this->penyebut($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = $this->penyebut($nilai / 100) . " ratus" . $this->penyebut($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " seribu" . $this->penyebut($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = $this->penyebut($nilai / 1000) . " ribu" . $this->penyebut($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = $this->penyebut($nilai / 1000000) . " juta" . $this->penyebut($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = $this->penyebut($nilai / 1000000000) . " milyar" . $this->penyebut(fmod($nilai, 1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = $this->penyebut($nilai / 1000000000000) . " trilyun" . $this->penyebut(fmod($nilai, 1000000000000));
        }
        return $temp;
    }

    function terbilang($nilai)
    {
        if ($nilai < 0) {
            $hasil = "minus " . trim($this->penyebut($nilai));
        } else {
            $hasil = trim($this->penyebut($nilai));
        }
        return $hasil . " rupiah";
    }

    public function getUserRoles()
    {
        $roles = User::find(auth()->user()->id)->getRoleNames()->toArray();
        // $roles = "Ninja";
        return $roles;
    }

    public function getLastOutgoing($payreq_id)
    {
        $lastOutgoing = Outgoing::where('payreq_id', $payreq_id)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastOutgoing;
    }

    public function generateDraftRealizationNumber()
    {
        $status_include = ['draft', 'submitted', 'rejected'];
        $realization_project_count = Realization::where('project', auth()->user()->project)
            ->whereIn('status', $status_include)
            ->count();
        $nomor = 'RQ' . Carbon::now()->addHours(8)->format('y') . substr(auth()->user()->project, 0, 3) . str_pad($realization_project_count + 1, 3, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateRealizationNumber($realization_id)
    {
        $realization = Realization::findOrFail($realization_id);
        $realization_project_count = Realization::where('project', $realization->payreq->project)
            ->where('status', 'approved')
            ->count();
        $nomor = Carbon::now()->format('y') . 'R' . substr(auth()->user()->project, 1, 2)  . str_pad($realization->id, 5, '0', STR_PAD_LEFT);
        // $nomor = Carbon::now()->format('y') . substr(auth()->user()->project, 0, 3)  . str_pad($realization_project_count + 1, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateCashJournalNumber($journal_id, $type)
    {

        $journal_type = $type == 'cash-out' ? 'COJ' : 'CIJ';
        $nomor = Carbon::now()->format('y') . $journal_type . substr(auth()->user()->project, 0, 3)  . str_pad($journal_id, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateVerificationJournalNumber($journal_id)
    {
        $nomor = Carbon::now()->format('y') . 'VJ' . substr(auth()->user()->project, 0, 3)  . str_pad($journal_id, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateManualIncomingNumber($incoming_id)
    {
        $nomor = Carbon::now()->format('y') . 'MIJ' . substr(auth()->user()->project, 0, 3)  . str_pad($incoming_id, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function getEquipments($project = null)
    {
        $url = env('URL_EQUIPMENTS');

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', $url);
            if ($response->getStatusCode() >= 500) {
                //
                return ['count' => 0, 'data' => [['unit_code' => 'server error']]];
            }
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // server error
            return ['count' => 0, 'data' => [['unit_code' => 'server error']]];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // client error
            return ['count' => 0, 'data' => [['unit_code' => 'server error']]];
        }

        // $response = $client->request('GET', $url);

        $equipments = json_decode($response->getBody()->getContents(), true)['data'];

        if ($project) {
            $equipments = array_filter($equipments, function ($item) use ($project) {
                return $item['project'] == $project;
            });
        }

        return $equipments;
    }

    /**
     * Get approval document counts for API
     * 
     * Returns the count of pending approval documents as JSON
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function approval_documents_count_api()
    {
        $document_count = $this->approval_documents_count();

        return response()->json($document_count);
    }

    /**
     * Get approval document counts
     * 
     * Returns the count of pending approval documents
     * 
     * @return array
     */
    public function approval_documents_count()
    {
        $approval_request_for_payreqs = ApprovalPlan::where('is_open', 1)
            ->where('document_type', 'payreq')
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->count();

        $approval_request_for_realizations = ApprovalPlan::where('is_open', 1)
            ->where('document_type', 'realization')
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->count();

        $approval_request_for_rabs = ApprovalPlan::where('is_open', 1)
            ->where('document_type', 'rab')
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->count();

        $approval = [
            'payreq' => $approval_request_for_payreqs,
            'realization' => $approval_request_for_realizations,
            'rab' => $approval_request_for_rabs,
        ];

        return $approval;
    }

    public function getPaidDate($payreq_id)
    {
        $payreq = Payreq::findOrFail($payreq_id);
        $outgoings = Outgoing::where('payreq_id', $payreq_id)->get();

        // check if payreq amount === sum of outgoings
        if ($outgoings->sum('amount') < $payreq->amount) {
            return null;
        } else {
            $lastOutgoing = Outgoing::where('payreq_id', $payreq_id)
                ->orderBy('created_at', 'desc')
                ->first();

            return $lastOutgoing->outgoing_date;
        };
    }

    public function getApproversName($document_id, $document_type)
    {
        $approvers = ApprovalPlan::where('document_id', $document_id)
            ->where('document_type', $document_type)
            ->where('status', 1)
            ->where('is_open', 1)
            ->get();

        $approvers_name = [];
        foreach ($approvers as $approver) {
            $approvers_name[] = $approver->approver->name;
        }

        return $approvers_name;
    }

    public function get_projects($project)
    {
        if ($project === '000H') {
            $projects = ['000H', 'APS'];
        } else {
            $projects = [$project];
        }

        return $projects;
    }
}
