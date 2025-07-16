<?php

namespace App\Http\Controllers\Cashier;

use App\Exports\BilyetTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\BilyetController as ReportsBilyetController;
use App\Http\Controllers\UserController;
use App\Models\Bilyet;
use App\Models\BilyetTemp;
use App\Models\Giro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BilyetController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page', 'dashboard');
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['admin', 'superadmin'], $userRoles)) {
            $giros = Giro::with('bank')->orderBy('acc_no')->get();
        } else {
            $giros = Giro::with('bank')->where('project', auth()->user()->project)->orderBy('acc_no')->get();
        }

        $views = [
            'dashboard' => 'cashier.bilyets.dashboard',
            'list' => 'cashier.bilyets.list',           // New unified list page
            'upload' => 'cashier.bilyets.upload',
        ];

        // Add fallback for invalid page parameters
        $allowedPages = ['dashboard', 'list', 'upload'];
        if (!in_array($page, $allowedPages)) {
            $page = 'dashboard'; // fallback to dashboard
        }

        if ($page === 'dashboard') {
            $data = app(ReportsBilyetController::class)->dashboardData();
            return view($views[$page], compact('data'));
        } elseif ($page === 'list') {
            // Get all bilyets for filtering - will be loaded via AJAX
            // Also get onhand bilyets for update many modal
            if (array_intersect(['admin', 'superadmin'], $userRoles)) {
                $onhands = Bilyet::where('status', 'onhand')
                    ->with(['giro.bank'])
                    ->orderBy('prefix', 'asc')->orderBy('nomor', 'asc')
                    ->get();
            } else {
                $onhands = Bilyet::where('status', 'onhand')
                    ->where('project', auth()->user()->project)
                    ->with(['giro.bank'])
                    ->orderBy('prefix', 'asc')->orderBy('nomor', 'asc')
                    ->get();
            }

            return view($views[$page], compact('giros', 'onhands'));
        } elseif ($page === 'upload') {
            // count giro_id that is null
            $giro_id_null = BilyetTemp::where('giro_id', null)->where('created_by', auth()->user()->id)->count();

            // cek data exist atau ngga
            $exist = BilyetTemp::where('created_by', auth()->user()->id)->exists();

            // cek duplikasi dan duplikasi tabel tujuan
            $duplikasi = app(BilyetTempController::class)->cekDuplikasi();
            $duplikasi_bilyet = app(BilyetTempController::class)->cekDuplikasiTabelTujuan();

            // jika ada giro_id yang null atau duplikasi, disable button import
            $import_button = !$exist || $giro_id_null > 0 || !empty($duplikasi) || !empty($duplikasi_bilyet) ? 'disabled' : null;
            $empty_button = $exist ? null : 'disabled';

            return view($views[$page], compact('giros', 'import_button', 'empty_button', 'duplikasi', 'duplikasi_bilyet'));
        } else {
            return view($views[$page], compact('giros'));
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'prefix' => 'required',
            'nomor' => 'required',
            'giro_id' => 'required',
        ]);

        $request->merge([
            'status' => $request->amount || $request->bilyet_date || $request->cair_date ? 'release' : 'onhand'
        ]);

        Bilyet::create($request->all());

        // Check referer to redirect properly to list page if coming from there
        if (strpos($request->headers->get('referer'), 'page=list') !== false) {
            return redirect()->route('cashier.bilyets.index', ['page' => 'list'])->with('success', 'Bilyet created successfully.');
        }

        return redirect()->back()->with('success', 'Bilyet created successfully.');
    }

    public function update(Request $request, $id)
    {
        $bilyet = Bilyet::find($id);

        if ($request->is_void) {
            // For void, only update status - don't change other fields
            $bilyet->update([
                'status' => 'void',
            ]);
        } else {
            if ($request->amount && $request->bilyet_date && $request->cair_date) {
                $status = 'cair';
            } elseif ($request->amount || $request->bilyet_date) {
                $status = 'release';
            } else {
                $status = 'onhand';
            }

            $bilyet->update([
                'bilyet_date' => $request->bilyet_date,
                'cair_date' => $request->cair_date,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
                'status' => $status,
            ]);
        }

        return redirect()->back()->with('success', 'Bilyet updated successfully.');
    }

    public function void($id)
    {
        $bilyet = Bilyet::find($id);
        $userRoles = app(UserController::class)->getUserRoles();

        if (!$bilyet) {
            return redirect()->back()->with('error', 'Bilyet not found.');
        }

        // Check access control - user can only void bilyets from their project
        if (!array_intersect(['admin', 'superadmin'], $userRoles) && $bilyet->project !== auth()->user()->project) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        if ($bilyet->status === 'void') {
            return redirect()->back()->with('warning', 'Bilyet is already voided.');
        }

        $bilyet->update(['status' => 'void']);

        return redirect()->back()->with('success', 'Bilyet voided successfully. Status changed to void while preserving other data.');
    }

    public function export()
    {
        return Excel::download(new BilyetTemplateExport, 'bilyet_template.xlsx');
    }

    public function destroy($id)
    {
        Bilyet::destroy($id);

        return redirect()->back()->with('success', 'Bilyet deleted successfully.');
    }

    public function import(Request $request)
    {
        // get all data from bilyet_temp
        $bilyets = BilyetTemp::where('created_by', auth()->user()->id)->get();
        $receive_date = $request->receive_date;

        // insert data to bilyet table
        foreach ($bilyets as $bilyet) {
            // $status = $bilyet->amount || $bilyet->bilyet_date || $bilyet->cair_date ? 'release' : 'onhand';

            if ($bilyet->amount && $bilyet->bilyet_date && $bilyet->cair_date) {
                $status = 'cair';
            } else {
                $status = $bilyet->amount || $bilyet->bilyet_date ? 'release' : 'onhand';
            }

            Bilyet::create([
                'giro_id' => $bilyet->giro_id,
                'prefix' => $bilyet->prefix,
                'nomor' => $bilyet->nomor,
                'type' => $bilyet->type,
                'receive_date' => $receive_date,
                'bilyet_date' => $bilyet->bilyet_date,
                'cair_date' => $bilyet->cair_date,
                'amount' => $bilyet->amount,
                'remarks' => $bilyet->remarks,
                'loan_id' => $bilyet->loan_id,
                'status' => $status,
                'created_by' => $bilyet->created_by,
                'project' => $bilyet->project,
            ]);
        }

        // delete all data from bilyet_temp
        BilyetTemp::where('created_by', auth()->user()->id)->delete();

        // return to the index page with success message
        return redirect()->back()->with('success', 'Bilyet imported successfully.');
    }

    public function update_many(Request $request)
    {
        // return $request->all();

        $bilyets = Bilyet::whereIn('id', $request->bilyet_ids)->get();

        foreach ($bilyets as $bilyet) {
            $bilyet->update([
                'bilyet_date' => $request->bilyet_date,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
                'status' => 'release',
            ]);
        }

        // Check from_page to redirect properly
        if ($request->from_page === 'list') {
            return redirect()->route('cashier.bilyets.index', ['page' => 'list'])->with('success', 'Bilyet updated successfully.');
        }

        return redirect()->route('cashier.bilyets.index')->with('success', 'Bilyet updated successfully.');
    }

    public function data(Request $request)
    {
        $userRoles = app(UserController::class)->getUserRoles();

        // Check if any filter is applied
        $hasFilter = $this->hasAnyFilter($request);

        // Debug logging
        \Illuminate\Support\Facades\Log::info('Bilyet data request', [
            'status' => $request->query('status'),
            'giro_id' => $request->query('giro_id'),
            'nomor' => $request->query('nomor'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'user_roles' => $userRoles,
            'has_filter' => $hasFilter
        ]);

        // If no filter applied, return empty result
        if (!$hasFilter) {
            return datatables()->of(collect())->toJson();
        }

        // Base query with eager loading for performance
        $query = Bilyet::query()
            ->whereNotNull('giro_id')
            ->with([
                'giro' => function ($query) {
                    $query->select('id', 'bank_id', 'curr', 'acc_no');
                },
                'giro.bank' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
            ->select('id', 'giro_id', 'prefix', 'nomor', 'type', 'bilyet_date', 'cair_date', 'amount', 'status', 'remarks', 'project', 'created_at');

        // Apply filters
        $this->applyStatusFilter($query, $request->query('status'));
        $this->applyGiroFilter($query, $request->query('giro_id'));
        $this->applyNomorFilter($query, $request->query('nomor'));
        $this->applyDateFilter($query, $request->query('date_from'), $request->query('date_to'));

        // Role-based access control
        if (!array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', auth()->user()->project);
        }

        // Get data
        $bilyets = $query->orderBy('bilyet_date', 'asc')->get();

        \Illuminate\Support\Facades\Log::info('Bilyet data result count: ' . $bilyets->count());

        return datatables()->of($bilyets)
            ->editColumn('account', function ($bilyet) {
                $remarks = $bilyet->remarks ? $bilyet->remarks : '';
                $bankName = $bilyet->giro && $bilyet->giro->bank ? $bilyet->giro->bank->name : 'N/A';
                $currency = $bilyet->giro ? strtoupper($bilyet->giro->curr) : 'N/A';
                $accNo = $bilyet->giro ? $bilyet->giro->acc_no : 'N/A';
                return '<small>' . $bankName . ' ' . $currency . ' | ' . $accNo . '<br>' . $remarks . '</small>';
            })
            ->editColumn('nomor', function ($bilyet) {
                return $bilyet->prefix . $bilyet->nomor;
            })
            ->editColumn('type', function ($bilyet) {
                return strtoupper($bilyet->type);
            })
            ->editColumn('bilyet_date', function ($bilyet) {
                return $bilyet->bilyet_date ? date('d-M-Y', strtotime($bilyet->bilyet_date)) : '-';
            })
            ->editColumn('cair_date', function ($bilyet) {
                return $bilyet->cair_date ? date('d-M-Y', strtotime($bilyet->cair_date)) : '-';
            })
            ->editColumn('status', function ($bilyet) {
                $statusBadges = [
                    'onhand' => '<span class="badge badge-primary">Onhand</span>',
                    'release' => '<span class="badge badge-warning">Release</span>',
                    'cair' => '<span class="badge badge-success">Cair</span>',
                    'void' => '<span class="badge badge-danger">Void</span>',
                ];
                return $statusBadges[$bilyet->status] ?? $bilyet->status;
            })
            ->editColumn('amount', function ($bilyet) {
                if ($bilyet->amount && $bilyet->amount > 0) {
                    return '<span class="amount-value" data-amount="' . $bilyet->amount . '">'
                        . number_format($bilyet->amount, 0, ',', '.') . ',-</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('checkbox', function ($bilyet) {
                // Only show checkbox for bilyets with amount
                if ($bilyet->amount && $bilyet->amount > 0) {
                    return '<div class="text-center">
                                <input type="checkbox" class="bilyet-checkbox"
                                       data-id="' . $bilyet->id . '"
                                       data-amount="' . $bilyet->amount . '"
                                       data-status="' . $bilyet->status . '"
                                       data-type="' . strtoupper($bilyet->type) . '"
                                       value="' . $bilyet->id . '">
                            </div>';
                }
                return '<div class="text-center"><span class="text-muted">-</span></div>';
            })
            ->addColumn('action', function ($bilyet) {
                try {
                    return view('cashier.bilyets.list_action', ['model' => $bilyet])->render();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error rendering bilyet action template', [
                        'bilyet_id' => $bilyet->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    return '<span class="text-danger">Error</span>';
                }
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'account', 'status', 'checkbox', 'amount'])
            ->toJson();
    }

    private function applyStatusFilter($query, $status)
    {
        if ($status && $status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }
    }

    private function applyGiroFilter($query, $giroId)
    {
        if ($giroId && $giroId !== '') {
            $query->where('giro_id', $giroId);
        }
    }

    private function applyNomorFilter($query, $nomor)
    {
        if ($nomor) {
            $query->where(function ($q) use ($nomor) {
                $q->where('nomor', 'LIKE', "%{$nomor}%")
                    ->orWhereRaw("CONCAT(prefix, nomor) LIKE ?", ["%{$nomor}%"]);
            });
        }
    }

    private function applyDateFilter($query, $dateFrom, $dateTo)
    {
        if ($dateFrom && $dateTo) {
            $query->whereBetween('bilyet_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('bilyet_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('bilyet_date', '<=', $dateTo);
        }
    }

    private function hasAnyFilter($request)
    {
        $status = $request->query('status');
        $giroId = $request->query('giro_id');
        $nomor = $request->query('nomor');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        return !empty($status) || !empty($giroId) || !empty($nomor) || !empty($dateFrom) || !empty($dateTo);
    }
}
