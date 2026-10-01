<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginAudit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginAuditController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $audits = LoginAudit::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('username_dicoba', 'like', '%'.$search.'%');
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('admin.login-audits.index', [
            'audits' => $audits,
            'search' => $search,
        ]);
    }
}
