<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function members_data()
    {
        $leader_id = auth()->id();

        $members = Team::where('leader_id', $leader_id)->get();

        if ($members->isEmpty()) {
            return [];
        }

        $filteredMembers = $members->filter(function ($member) {
            $ongoings = $this->getMemberOngoings($member->member_id);
            if ($ongoings->isNotEmpty()) {
                $member->ongoings = $ongoings;
                $member->name = $this->getMemberName($member->member_id);
                return true;
            }
            return false;
        });

        return $filteredMembers->values();
    }

    private function getMemberOngoings($user_id)
    {
        $ongoings = Payreq::join('outgoings', 'payreqs.id', '=', 'outgoings.payreq_id')
            ->select('payreqs.id', 'payreqs.nomor', 'payreqs.remarks', 'payreqs.status', 'outgoings.outgoing_date')
            ->where('payreqs.user_id', $user_id)
            ->whereIn('payreqs.status', ['paid', 'realization'])
            ->orderBy('outgoings.outgoing_date', 'asc')
            ->get();

        $ongoings = $ongoings->unique('id')->sortByDesc('outgoing_date');

        // return $ongoings;

        if ($ongoings->isEmpty()) {
            return collect();
        }

        return $ongoings->map(function ($payreq) {
            $payreq->description = 'PR no.' . $payreq->nomor . ', ' . $payreq->remarks;
            $payreq->amount = number_format($this->sumAmountPayreqOutgoings($payreq->id), 0, ',', '.') . ',-';
            $payreq->days = $this->calculateDays($payreq->outgoing_date);
            return $payreq;
        });
    }

    private function getMemberName($user_id)
    {
        return Team::where('member_id', $user_id)->first()->member->name;
    }

    private function sumAmountPayreqOutgoings($payreq_id)
    {
        return Payreq::find($payreq_id)->outgoings->sum('amount');
    }

    private function calculateDays($outgoing_date)
    {
        $now = now();
        $outgoing_date = new \DateTime($outgoing_date);
        $interval = $now->diff($outgoing_date);

        return $interval->days;
    }
}
