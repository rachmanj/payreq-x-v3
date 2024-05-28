<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Migrasi\MigrasiPayreqController;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\VerificationJournal;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        // $test = app(CashierDashboardController::class)->dashboard_data();
        // $test = app(Reports\OngoingDashboardController::class)->get_verifikasi_belum_posted(['000H', 'APS']);
        // $test = app(UserPayreqController::class)->ongoing_payreqs();
        // $test = Realization::with('realizationDetails')->where('id', 483)->first();
        // $test = app(UserRealizationController::class)->ongoing_realizations();
        // $test = Realization::with('realizationDetails')->where('id', 3)->first();
        // $test = app(CashInJournalController::class)->to_cart_data();
        // $test = app(VerificationJournalController::class)->journal_details(30);
        // $test = app(DashboardUserController::class)->user_monthly_amount();
        // $test = app(DocumentNumberController::class)->generate_document_number('pcbc', '000H');
        // $test = app(DocumentNumberController::class)->generate_draft_document_number('000H');
        // $test = app(Reports\EquipmentController::class)->detail('VA 045');
        // $test = app(BucSyncController::class)->get_buc_payreqs();
        // $test = app(BucSyncController::class)->cek_rab_id();
        // $test = app(Reports\LoanController::class)->dashboard_data();
        // $test = app(Reports\OngoingDashboardController::class)->dashboard_data('017C');
        // $test = app(Reports\OngoingDashboardController::class)->ongoing_documents_by_user('000H');
        // $test = app(VerificationJournalController::class)->journal_details(2);
        // $test = app(MigrasiController::class)->checkIsDataExist();
        // $test = app(MigrasiPayreqController::class)->update();
        // $test = app(ToolController::class)->getApproversName(1812, 'payreq');
        $test = app(Reports\EomController::class)->eom_journal();
        // $test = $this->get_realization();


        // $realizations = Realization::where('flag', 'VJTEMP' . auth()->user()->id)
        //     ->get();

        // $realization_details = $realizations->pluck('realizationDetails')->flatten();
        // $test = $realization_details->sum('amount');

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
        // get realization include with verification_journal and realization_details
        // $realizations = Realization::select('id', 'nomor', 'created_at', 'verification_journal_id', 'status')
        $realizations = Realization::select('verification_journal_id')
            ->whereIn('id', $this->cek_realization_posted())
            ->where('status', 'verification-complete')
            ->distinct('verification_journal_id')
            ->orderBy('verification_journal_id', 'asc')
            ->get();

        // foreach ($realizations as $realization) {
        //     // $realization->status_before = $realization->status;

        //     // $realization_after = Realization::where('id', $realization->id)->first()
        //     //     ->update([
        //     //         'status' => 'close'
        //     //     ]);

        //     // $realization->status_after = $realization_after;

        //     $realization->verification_journal = VerificationJournal::select('id', 'sap_journal_no', 'sap_posting_date')->where('id', $realization->verification_journal_id)
        //         ->first();
        // }

        return [
            'realization_count' => $realizations->count(),
            'realizations' => $realizations
        ];
    }
}
