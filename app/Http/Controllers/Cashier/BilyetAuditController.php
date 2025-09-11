<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\BilyetAudit;
use App\Models\Bilyet;
use Illuminate\Http\Request;

class BilyetAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = BilyetAudit::with(['bilyet.giro.bank', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by bilyet if specified
        if ($request->has('bilyet_id') && $request->bilyet_id) {
            $query->where('bilyet_id', $request->bilyet_id);
        }

        // Filter by action if specified
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by user if specified
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits = $query->paginate(50);

        return view('cashier.bilyets.audit', compact('audits'));
    }

    public function show($id)
    {
        $audit = BilyetAudit::with(['bilyet.giro.bank', 'user'])->findOrFail($id);

        return view('cashier.bilyets.audit_detail', compact('audit'));
    }

    public function bilyetHistory($bilyetId)
    {
        $bilyet = Bilyet::with(['giro.bank', 'creator'])->findOrFail($bilyetId);
        $audits = BilyetAudit::with('user')
            ->where('bilyet_id', $bilyetId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cashier.bilyets.history', compact('bilyet', 'audits'));
    }
}
