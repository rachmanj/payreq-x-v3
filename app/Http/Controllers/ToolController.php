<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
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
        $client = new \GuzzleHttp\Client;
        $response = $client->request('GET', $url);
        $projects = json_decode($response->getBody()->getContents(), true)['data'];

        return $projects;
    }

    public function penyebut($nilai)
    {
        $nilai = abs($nilai);
        $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $temp = '';
        if ($nilai < 12) {
            $temp = ' '.$huruf[$nilai];
        } elseif ($nilai < 20) {
            $temp = $this->penyebut($nilai - 10).' belas';
        } elseif ($nilai < 100) {
            $temp = $this->penyebut($nilai / 10).' puluh'.$this->penyebut($nilai % 10);
        } elseif ($nilai < 200) {
            $temp = ' seratus'.$this->penyebut($nilai - 100);
        } elseif ($nilai < 1000) {
            $temp = $this->penyebut($nilai / 100).' ratus'.$this->penyebut($nilai % 100);
        } elseif ($nilai < 2000) {
            $temp = ' seribu'.$this->penyebut($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $temp = $this->penyebut($nilai / 1000).' ribu'.$this->penyebut($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            $temp = $this->penyebut($nilai / 1000000).' juta'.$this->penyebut($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            $temp = $this->penyebut($nilai / 1000000000).' milyar'.$this->penyebut(fmod($nilai, 1000000000));
        } elseif ($nilai < 1000000000000000) {
            $temp = $this->penyebut($nilai / 1000000000000).' trilyun'.$this->penyebut(fmod($nilai, 1000000000000));
        }

        return $temp;
    }

    public function terbilang($nilai)
    {
        if ($nilai < 0) {
            $hasil = 'minus '.trim($this->penyebut($nilai));
        } else {
            $hasil = trim($this->penyebut($nilai));
        }

        return $hasil.' rupiah';
    }

    public function numberToWordsEnglish(float $amount): string
    {
        $amount = round($amount, 2);
        $sign = '';
        if ($amount < 0) {
            $sign = 'MINUS ';
            $amount = abs($amount);
        }
        $rupeesInteger = (int) floor($amount);
        $cents = (int) round(($amount - $rupeesInteger) * 100);
        $cents = min(99, $cents);

        $rupiahWords = strtoupper($this->englishWordsForNonNegativeInteger($rupeesInteger));
        $centWords = strtoupper($this->englishWordsForNonNegativeInteger($cents));

        return $sign.$rupiahWords.' RUPIAH POINT '.$centWords.' CENTS';
    }

    private function englishWordsForNonNegativeInteger(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        $underHundred = function (int $n) use ($ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            $tenPortion = intdiv($n, 10);
            $rest = $n % 10;
            if ($rest === 0) {
                return $tens[$tenPortion];
            }

            return $tens[$tenPortion].'-'.$ones[$rest];
        };

        $underThousand = function (int $n) use ($underHundred, $ones): string {
            if ($n < 100) {
                return $underHundred($n);
            }
            $hundredsDigit = intdiv($n, 100);
            $rest = $n % 100;
            $prefix = $ones[$hundredsDigit].' hundred';
            if ($rest === 0) {
                return $prefix;
            }

            return $prefix.' '.$underHundred($rest);
        };

        $parts = [];
        $billion = intdiv($number, 1000000000);
        $remainder = $number % 1000000000;
        $million = intdiv($remainder, 1000000);
        $remainder = $remainder % 1000000;
        $thousand = intdiv($remainder, 1000);
        $remainder = $remainder % 1000;

        if ($billion > 0) {
            $parts[] = $underThousand($billion).' billion';
        }
        if ($million > 0) {
            $parts[] = $underThousand($million).' million';
        }
        if ($thousand > 0) {
            $parts[] = $underThousand($thousand).' thousand';
        }
        if ($remainder > 0) {
            $parts[] = $underThousand($remainder);
        }

        return implode(' ', $parts);
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
        $nomor = 'RQ'.Carbon::now()->addHours(8)->format('y').substr(auth()->user()->project, 0, 3).str_pad($realization_project_count + 1, 3, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateRealizationNumber($realization_id)
    {
        $realization = Realization::findOrFail($realization_id);
        $realization_project_count = Realization::where('project', $realization->payreq->project)
            ->where('status', 'approved')
            ->count();
        $nomor = Carbon::now()->format('y').'R'.substr(auth()->user()->project, 1, 2).str_pad($realization->id, 5, '0', STR_PAD_LEFT);
        // $nomor = Carbon::now()->format('y') . substr(auth()->user()->project, 0, 3)  . str_pad($realization_project_count + 1, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateCashJournalNumber($journal_id, $type)
    {

        $journal_type = $type == 'cash-out' ? 'COJ' : 'CIJ';
        $nomor = Carbon::now()->format('y').$journal_type.substr(auth()->user()->project, 0, 3).str_pad($journal_id, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateVerificationJournalNumber($journal_id)
    {
        $nomor = Carbon::now()->format('y').'VJ'.substr(auth()->user()->project, 0, 3).str_pad($journal_id, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generateManualIncomingNumber($incoming_id)
    {
        $nomor = Carbon::now()->format('y').'MIJ'.substr(auth()->user()->project, 0, 3).str_pad($incoming_id, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function getEquipments($project = null)
    {
        $url = env('URL_EQUIPMENTS');

        $client = new \GuzzleHttp\Client;

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
        }
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
