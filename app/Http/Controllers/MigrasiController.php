<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MigrasiController extends Controller
{
    public function index()
    {
        $check_tables = $this->checkIsDataExist();

        return view('migrasi.index', compact([
            'check_tables',
        ]));
    }

    public function getMigrasiData()
    {
        // get data from http://localhost/payreq-x/api/migrasi
        $url = 'http://localhost/payreq-x/api/migrasi/payreqs';
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $data = json_decode($response->getBody()->getContents(), true);

        $payreqs = $data['migrasi_data']['payreqs'];

        $convertedPayreqs = [];
        foreach ($payreqs as $payreq) {
            $payreq['department_id'] = $this->departmentIdConversion($payreq['department_id']);
            $convertedPayreqs[] = $payreq;
        }

        return $data;
    }

    public function checkIsDataExist()
    {
        $isUsers = \App\Models\User::exists() ? true : false;
        $isPayreqs = \App\Models\Payreq::count() > 0 ? true : false;
        $isApprovalPlans = \App\Models\ApprovalPlan::exists() ? true : false;
        $isRabs = \App\Models\Rab::exists() ? true : false;
        $isRealizations = \App\Models\Realization::exists() ? true : false;
        $isRealizationDetails = \App\Models\RealizationDetail::exists() ? true : false;
        $isCashJournals = \App\Models\CashJournal::exists() ? true : false;
        $isTransactions = \App\Models\Transaction::exists() ? true : false;
        $isVerificationJournalDetails = \App\Models\VerificationJournalDetail::exists() ? true : false;
        $isJournalDetails = \App\Models\JournalDetail::exists() ? true : false; // journal_details : cost_center_id
        $isGeneralLedgers = \App\Models\GeneralLedger::exists() ? true : false; // general_ledgers : cost_center_id

        return [
            ['table_name' => 'users', 'is_exist' => $isUsers],
            ['table_name' => 'payreqs', 'is_exist' => $isPayreqs],
            ['table_name' => 'approval_plans', 'is_exist' => $isApprovalPlans],
            ['table_name' => 'rabs', 'is_exist' => $isRabs],
            ['table_name' => 'realizations', 'is_exist' => $isRealizations],
            ['table_name' => 'realization_details', 'is_exist' => $isRealizationDetails],
            ['table_name' => 'cash_journals', 'is_exist' => $isCashJournals],
            ['table_name' => 'transactions', 'is_exist' => $isTransactions],
            ['table_name' => 'verification_journal_details', 'is_exist' => $isVerificationJournalDetails],
            ['table_name' => 'journal_details', 'is_exist' => $isJournalDetails],
            ['table_name' => 'general_ledgers', 'is_exist' => $isGeneralLedgers],
        ];
    }

    // function that update department_id from old system to new system
    public function updateDepartmentId()
    {
        $payreqs = \App\Models\User::all();

        foreach ($payreqs as $payreq) {
            $payreq->department_id = $this->departmentIdConversion($payreq->department_id);
            $payreq->save();
        }

        return redirect()->route('migrasi.index')->with('success', 'Department ID has been updated successfully');
    }


    public function departmentIdConversion($originalDepartmentId)
    {
        switch ($originalDepartmentId) {
            case 1:
                return 5;
            case 2:
                return 14;
            case 3:
                return 19;
            case 4:
                return 12;
            case 5:
                return 10;
            case 6:
                return 15;
            case 7:
                return 13;
            case 8:
                return 20;
            case 9:
                return 8;
            case 10:
                return 11;
            case 12:
                return 4;
            case 13:
                return 18;
            case 14:
                return 17;
            case 16:
                return 6;
            default:
                return 0;
        }
    }
}
