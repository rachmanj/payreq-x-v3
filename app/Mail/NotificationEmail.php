<?php

namespace App\Mail;

use App\Models\Payreq;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;


    public function __construct()
    {
        //
    }

    public function build()
    {
        // $outstanding_payreq = $this->not_realization()->sum('payreq_idr') + $this->not_verify()->sum('payreq_idr');
        $payreq_user = User::where('notif_flag', 'Y' . auth()->user()->id)->first();

        return $this->from('notification@it.arka.co.id')
            ->view('realization.notification')
            ->with(
                [
                    'just_approved' => $this->just_approved($payreq_user->id),
                    'not_realization' => $this->not_realization($payreq_user->id),
                    'not_verify' => $this->not_verify($payreq_user->id),
                    'user' => $payreq_user->name,
                ]
            );
    }

    public function just_approved($user_id)
    {
        return Payreq::where('user_id', $user_id)
            ->whereNull('outgoing_date')
            ->select('id', 'payreq_num', 'payreq_idr', 'payreq_type', 'approve_date')
            ->selectRaw('datediff(now(), approve_date) as days')
            ->orderBy('approve_date', 'asc');
    }

    public function not_realization($user_id)
    {
        return Payreq::where('user_id', $user_id)
            ->where('payreq_type', 'advance')
            ->whereNotNull('outgoing_date')
            ->whereNull('realization_date')
            ->select('id', 'payreq_num', 'payreq_idr', 'outgoing_date', 'realization_date')
            ->selectRaw('datediff(now(), outgoing_date) as days')
            ->orderBy('outgoing_date', 'asc');
    }

    public function not_verify($user_id)
    {
        return Payreq::where('user_id', $user_id)
            ->where('payreq_type', 'advance')
            ->whereNotNull('outgoing_date')
            ->whereNotNull('realization_date')
            ->whereNull('verify_date')
            ->select('id', 'payreq_num', 'payreq_idr', 'outgoing_date', 'realization_date', 'realization_num')
            ->selectRaw('datediff(now(), realization_date) as days')
            ->orderBy('realization_date', 'asc');
    }
}
