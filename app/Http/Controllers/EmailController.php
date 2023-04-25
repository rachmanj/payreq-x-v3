<?php

namespace App\Http\Controllers;

use App\Mail\NotificationEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function index()
    {
        return view('emails.index');
    }

    public function push($id)
    {
        $user = User::findOrfail($id);

        if (!$user->email) {
            return redirect()->route('emails.index')->with('error', 'Email not found!');
        }

        $user->notif_flag = 'Y' . auth()->user()->id;
        $user->save();

        Mail::to($user->email)->send(new NotificationEmail());

        $user->last_notif = Carbon::now();
        $user->notif_flag = null;
        $user->save();

        return redirect()->route('emails.index')->with('success', 'Email sent successfully!');
    }

    public function data()
    {
        $users = User::orderBy('name', 'asc')->get();

        return datatables()->of($users)
            ->editColumn('last_notif', function ($users) {
                return $users->last_notif ? Carbon::parse($users->last_notif)->diffForHumans()  : '';
            })
            ->addIndexColumn()
            ->addColumn('action', 'emails.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
