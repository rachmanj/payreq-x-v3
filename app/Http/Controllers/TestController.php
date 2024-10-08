<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Accounting\SapSyncController;
use App\Http\Controllers\Reports\BilyetController;
use App\Http\Controllers\Reports\CashierRekapAdvanceController;
use App\Http\Controllers\Reports\EomController;
use App\Http\Controllers\Reports\EquipmentController;
use App\Http\Controllers\Reports\OngoingDashboardController;
use App\Http\Controllers\UserPayreq\UserAnggaranController;
use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\VerificationJournal;

class TestController extends Controller
{
    public function index()
    {
        // $test = app(UserAnggaranController::class)->getAvailableRabs();
        // $test = app(EquipmentController::class)->fuelCostPerKM('VA 070');
        // $test = app(EquipmentController::class)->km_array('VA 063');
        // $test = app(SapSyncController::class)->chart_vj_postby();
        // $test = app(OngoingDashboardController::class)->dashboard_data('017C');
        $test = app(UserAnggaranController::class)->recalculate();
        // $test = app(BilyetController::class)->dashboardData();
        // $test = app(TeamController::class)->members_data();
        // $test = app(CashierRekapAdvanceController::class)->ongoing_documents_by_user('000H');
        // $test = app(CashierRekapAdvanceController::class)->advance_data('001H');
        // $test = app(EomController::class)->eom_journal(['000H']);
        // $test = $this->getDNCRabs();

        return $test;
    }

    public function join_array_test()
    {
        $user_payreqs_no_realization = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->whereDoesntHave('realization')
            ->get();

        $payreq_with_realization_rejected = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->whereHas('realization', function ($query) {
                $query->where('status', 'rejected');
            })
            ->distinct()
            ->get();

        // $realization_array = [];
        $realization_array = $user_payreqs_no_realization->merge($payreq_with_realization_rejected);

        return $realization_array;
    }

    public function cek_realization_posted()
    {
        // get verification_journal_id from verification_journal table where sap_journal_no is not null and make array of verification_journal_id
        $verification_journals = VerificationJournal::whereNotNull('sap_journal_no')
            ->pluck('id')
            ->toArray();

        // now get realization data where verification_journal_id is in array of verification_journal_id
        $realizations = Realization::whereIn('verification_journal_id', $verification_journals)
            ->pluck('id');

        return $realizations;
    }

    public function get_realization()
    {
        $realizations = Realization::select('verification_journal_id')
            ->whereIn('id', $this->cek_realization_posted())
            ->where('status', 'verification-complete')
            ->distinct('verification_journal_id')
            ->orderBy('verification_journal_id', 'asc')
            ->get();

        return [
            'realization_count' => $realizations->count(),
            'realizations' => $realizations
        ];
    }
}
