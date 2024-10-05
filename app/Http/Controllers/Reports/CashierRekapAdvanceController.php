<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;
use App\Models\Incoming;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use App\Models\VerificationJournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierRekapAdvanceController extends Controller
{
    public function index()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin', 'cashier'], $userRoles)) {
            $project = request()->query('project');
        } else {
            $project = auth()->user()->project;
        }

        $data = $this->advance_data($project);

        return view('reports.cashier.rekap_advance', compact('data'));
    }

    public function advance_data($project)
    {
        $result = [
            'project' => $project,
            'sum_all_amounts_by_project' => $this->sum_all_amounts_by_project($project),
            'list' => $this->ongoing_documents_by_user($project),
        ];

        return $result;
    }

    public function sum_all_amounts_by_project($project)
    {
        $users = $this->user_list($project);

        $totalAmount = $users->reduce(function ($carry, $user) {
            $carry += $this->payreqs_belum_realisasi_by_user_amount($user->id);
            $carry += $this->realisasi_belum_verifikasi_by_user_amount($user->id);
            $carry += $this->variance_realisasi_belum_incoming_by_user_amount($user->id);
            $carry -= $this->variance_realisasi_belum_outgoing_by_user_amount($user->id);
            return $carry;
        }, 0);

        return $totalAmount > 0 ? number_format($totalAmount, 2) : 0;
    }

    public function user_list($project)
    {
        $project = $this->get_projects($project);

        $users = User::select('id', 'name')
            ->whereIn('project', $project)
            ->orderBy('name', 'asc')
            ->get();

        return $users;
    }

    public function ongoing_documents_by_user_old($project)
    {
        $users = $this->user_list($project);

        $userIds = $users->pluck('id')->toArray();

        $payreqsBelumRealisasi = $this->get_payreqs_belum_realisasi_by_users($userIds);
        $realisasiBelumVerifikasi = $this->get_realisasi_belum_verifikasi_by_users($userIds);
        $varianceRealisasiBelumIncoming = $this->get_variance_realisasi_belum_incoming_by_users($userIds);
        $varianceRealisasiBelumOutgoing = $this->get_variance_realisasi_belum_outgoing_by_users($userIds);

        $filteredUsers = $users->filter(function ($user) use ($payreqsBelumRealisasi, $realisasiBelumVerifikasi, $varianceRealisasiBelumIncoming, $varianceRealisasiBelumOutgoing) {
            $user->payreq_belum_realisasi_amount = $this->format_amount($payreqsBelumRealisasi->where('user_id', $user->id)->sum('total_amount'));
            $user->realisasi_belum_verifikasi_amount = $this->format_amount($realisasiBelumVerifikasi->where('user_id', $user->id)->sum('total_amount'));
            $user->variance_realisasi_belum_incoming_amount = $this->format_amount($varianceRealisasiBelumIncoming->where('user_id', $user->id)->sum('amount'));
            $user->variance_realisasi_belum_outgoing_amount = $this->format_amount($varianceRealisasiBelumOutgoing->where('user_id', $user->id)->sum('amount'));
            $user->total_user_advance = $this->dana_belum_diselesaikan($user->id);
            // $user->payreq_belum_realisasi_list = $payreqsBelumRealisasi->where('user_id', $user->id);
            // $user->realisasi_belum_verifikasi_list = $realisasiBelumVerifikasi->where('user_id', $user->id);
            // $user->variance_realisasi_belum_incoming_list = $varianceRealisasiBelumIncoming->where('user_id', $user->id);
            // $user->variance_realisasi_belum_outgoing_list = $varianceRealisasiBelumOutgoing->where('user_id', $user->id);
            $user->display = $this->should_display_user($user->id);
            return $user->display;
        });

        return $filteredUsers->values();
    }

    public function ongoing_documents_by_user($project)
    {
        $users = $this->user_list($project);

        $userIds = $users->pluck('id')->toArray();

        $payreqsBelumRealisasi = $this->get_payreqs_belum_realisasi_by_users($userIds);
        $realisasiBelumVerifikasi = $this->get_realisasi_belum_verifikasi_by_users($userIds);
        $varianceRealisasiBelumIncoming = $this->get_variance_realisasi_belum_incoming_by_users($userIds);
        $varianceRealisasiBelumOutgoing = $this->get_variance_realisasi_belum_outgoing_by_users($userIds);

        $filteredUsers = $users->filter(function ($user) use ($payreqsBelumRealisasi, $realisasiBelumVerifikasi, $varianceRealisasiBelumIncoming, $varianceRealisasiBelumOutgoing) {
            $user->total_user_advance = $this->dana_belum_diselesaikan($user->id);
            $itemDetails = [
                [
                    'item_desc' => 'Payreq Belum Realisasi',
                    'item_amount' => $this->format_amount($payreqsBelumRealisasi->where('user_id', $user->id)->sum('total_amount')),
                ],
                [
                    'item_desc' => 'Realisasi Belum Verifikasi',
                    'item_amount' => $this->format_amount($realisasiBelumVerifikasi->where('user_id', $user->id)->sum('total_amount')),
                ],
                [
                    'item_desc' => 'Variance Realisasi Belum Incoming',
                    'item_amount' => $this->format_amount($varianceRealisasiBelumIncoming->where('user_id', $user->id)->sum('amount')),
                ],
                [
                    'item_desc' => 'Variance Realisasi Belum Outgoing (minus)',
                    'item_amount' => $this->format_amount($varianceRealisasiBelumOutgoing->where('user_id', $user->id)->sum('amount')),
                ],
            ];

            // Filter out items with amount 0
            $user->item_details = array_filter($itemDetails, function ($item) {
                return $item['item_amount'] != 0;
            });

            return $this->should_display_user($user->id);
        });

        return $filteredUsers->values();
    }

    private function get_payreqs_belum_realisasi_by_users($userIds)
    {
        return Outgoing::whereIn('payreq_id', function ($query) use ($userIds) {
            $query->select('id')->from('payreqs')->whereIn('status', ['paid', 'split'])->whereIn('user_id', $userIds);
        })
            ->join('payreqs', 'payreqs.id', '=', 'outgoings.payreq_id')
            ->select('payreqs.nomor as payreq_nomor', 'outgoings.outgoing_date as paid_date', 'payreqs.user_id', DB::raw('SUM(outgoings.amount) as total_amount'))
            ->groupBy('payreqs.nomor', 'outgoings.outgoing_date', 'payreqs.user_id')
            ->get();
    }

    private function get_realisasi_belum_verifikasi_by_users($userIds)
    {
        return RealizationDetail::whereIn('realization_id', function ($query) use ($userIds) {
            $query->select('id')->from('realizations')->whereIn('status', ['approved', 'reimburse-paid'])->whereIn('user_id', $userIds);
        })
            ->join('realizations', 'realizations.id', '=', 'realization_details.realization_id')
            ->select('realizations.nomor as realization_nomor', 'realizations.id as realization_id', 'realizations.user_id', DB::raw('SUM(realization_details.amount) as total_amount'), 'realizations.approved_at')
            ->groupBy('realizations.nomor', 'realizations.id', 'realizations.approved_at', 'realizations.user_id')
            ->get();
    }

    private function get_variance_realisasi_belum_incoming_by_users($userIds)
    {
        return Incoming::whereNull('receive_date')
            ->join('realizations', 'incomings.realization_id', '=', 'realizations.id')
            ->whereIn('realizations.user_id', $userIds)
            ->select('realizations.nomor as realization_nomor', 'incomings.amount', 'realizations.user_id')
            ->get();
    }

    private function get_variance_realisasi_belum_outgoing_by_users($userIds)
    {
        return Payreq::where('type', 'other')
            ->whereIn('user_id', $userIds)
            ->doesntHave('outgoings')
            ->select('nomor', 'amount', 'user_id')
            ->get();
    }

    private function format_amount($amount)
    {
        return $amount > 0 ? number_format($amount, 2) : 0;
    }

    private function should_display_user($user_id)
    {
        return $this->payreqs_belum_realisasi_by_user_amount($user_id) +
            $this->realisasi_belum_verifikasi_by_user_amount($user_id) +
            $this->variance_realisasi_belum_incoming_by_user_amount($user_id) +
            $this->variance_realisasi_belum_outgoing_by_user_amount($user_id) > 0;
    }

    public function dana_belum_diselesaikan($user_id)
    {
        $total = $this->payreqs_belum_realisasi_by_user_amount($user_id) + $this->realisasi_belum_verifikasi_by_user_amount($user_id) + $this->variance_realisasi_belum_incoming_by_user_amount($user_id) - $this->variance_realisasi_belum_outgoing_by_user_amount($user_id);
        return $total > 0 ? number_format($total, 2) : 0;
    }

    public function payreqs_belum_realisasi_by_user_amount($user_id)
    {
        return $this->get_payreqs_belum_realisasi_by_user($user_id)->sum('total_amount');
    }

    public function get_payreqs_belum_realisasi_by_user($user_id)
    {
        return Outgoing::whereIn('payreq_id', function ($query) use ($user_id) {
            $query->select('id')->from('payreqs')->whereIn('status', ['paid', 'split'])->where('user_id', $user_id);
        })
            ->join('payreqs', 'payreqs.id', '=', 'outgoings.payreq_id')
            ->select('payreqs.nomor as payreq_nomor', 'outgoings.outgoing_date as paid_date', DB::raw('SUM(outgoings.amount) as total_amount'))
            ->groupBy('payreqs.nomor', 'outgoings.outgoing_date')
            ->get();
    }

    public function realisasi_belum_verifikasi_by_user_amount($user_id)
    {
        return $this->get_realisasi_belum_verifikasi_by_user($user_id)->sum('total_amount');
    }

    public function get_realisasi_belum_verifikasi_by_user($user_id)
    {
        return RealizationDetail::whereIn('realization_id', function ($query) use ($user_id) {
            $query->select('id')->from('realizations')->whereIn('status', ['approved', 'reimburse-paid'])->where('user_id', $user_id);
        })
            ->join('realizations', 'realizations.id', '=', 'realization_details.realization_id')
            ->select('realizations.nomor as realization_nomor', 'realizations.id as realization_id', DB::raw('SUM(realization_details.amount) as total_amount'), 'realizations.approved_at')
            ->groupBy('realizations.nomor', 'realizations.id', 'realizations.approved_at')
            ->get();
    }

    public function variance_realisasi_belum_incoming_by_user_amount($user_id)
    {
        return $this->get_variance_realisasi_belum_incoming_by_user($user_id)->sum('amount');
    }

    public function get_variance_realisasi_belum_incoming_by_user($user_id)
    {
        return Incoming::whereNull('receive_date')
            ->join('realizations', 'incomings.realization_id', '=', 'realizations.id')
            ->where('realizations.user_id', $user_id)
            ->select('realizations.nomor as realization_nomor', 'incomings.amount')
            ->get();
    }

    public function variance_realisasi_belum_outgoing_by_user_amount($user_id)
    {
        return $this->get_variance_realisasi_belum_outgoing_by_user($user_id)->sum('amount');
    }

    public function get_variance_realisasi_belum_outgoing_by_user($user_id)
    {
        return Payreq::where('type', 'other')
            ->where('user_id', $user_id)
            ->doesntHave('outgoings')
            ->select('nomor', 'amount')
            ->get();
    }

    public function get_payreq_belum_realisasi($project)
    {
        return Payreq::whereIn('status', ['paid', 'split'])->whereIn('project', $project)->get();
    }

    public function get_realisasi_belum_verifikasi($project)
    {
        return Realization::whereIn('status', ['approved', 'reimburse-paid'])->whereIn('project', $project)->get();
    }

    public function get_verifikasi_belum_posted($project)
    {
        return VerificationJournalDetail::whereNull('sap_journal_no')
            ->whereIn('project', $project)
            ->where('debit_credit', 'debit')
            ->get();
    }

    public function get_payreq_other_belum_outgoing($project)
    {
        return Payreq::where('type', 'other')
            ->whereIn('project', $project)
            ->doesntHave('outgoings')
            ->get();
    }

    public function get_incomings_belum_diterima($project)
    {
        return Incoming::whereNull('receive_date')
            ->join('realizations', 'incomings.realization_id', '=', 'realizations.id')
            ->whereIn('incomings.project', $project)
            ->select('incomings.*', 'realizations.user_id')
            ->get();
    }

    public function get_projects($project)
    {
        return $project === '000H' ? ['000H', 'APS'] : [$project];
    }
}
