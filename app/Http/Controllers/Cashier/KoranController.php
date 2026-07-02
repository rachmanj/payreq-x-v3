<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\BankReconciliation;
use App\Models\Dokumen;
use App\Models\Giro;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KoranController extends Controller
{
    protected array $allowedRoles = ['admin', 'superadmin', 'cashier', 'approver_bo', 'cashier_bo', 'corsec'];

    public function index(): View
    {
        $page = request()->query('page', 'dashboard');
        $userRoles = app(UserController::class)->getUserRoles();

        $giros = $this->getGiros($userRoles);

        $views = [
            'dashboard' => 'cashier.koran.dashboard',
            'upload' => 'cashier.koran.upload',
        ];

        if ($page === 'dashboard') {
            $year = request()->query('year', date('Y'));
            $korans = $this->check_koran_files($year);
            $statistics = $this->calculateStatistics($korans);
            $canUploadKoran = auth()->user()->can('upload_koran');
            $canDeleteKoran = auth()->user()->can('delete_koran');
            $hasElevatedKoranAccess = (bool) array_intersect($this->allowedRoles, $userRoles);

            return view($views['dashboard'], compact(
                'giros',
                'year',
                'korans',
                'statistics',
                'canUploadKoran',
                'canDeleteKoran',
                'hasElevatedKoranAccess',
            ));
        }

        return view($views['upload'], compact('giros'));
    }

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file_upload' => 'required|mimes:pdf',
            'giro_id' => 'required|integer|exists:giros,id',
            'periode' => 'required|date_format:Y-m',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $giro = Giro::query()->findOrFail((int) $validated['giro_id']);
        $this->authorizeGiroAccess($giro);

        $periodeDate = Carbon::createFromFormat('Y-m', $validated['periode'])->startOfMonth();

        $exists = Dokumen::query()
            ->where('type', 'koran')
            ->where('giro_id', $giro->id)
            ->whereYear('periode', $periodeDate->year)
            ->whereMonth('periode', $periodeDate->month)
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'periode' => 'A statement for this account and month is already uploaded.',
                ]);
        }

        $filename = $this->uploadFile($request->file('file_upload'), $giro);

        Dokumen::create([
            'filename1' => $filename,
            'giro_id' => $giro->id,
            'type' => 'koran',
            'project' => $giro->project,
            'periode' => $periodeDate->format('Y-m-d'),
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => auth()->user()->id,
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully.');
    }

    public function destroy(Dokumen $dokumen): RedirectResponse
    {
        abort_unless($dokumen->type === 'koran', 404);
        abort_unless(auth()->user()->can('delete_koran'), 403);

        $dokumen->loadMissing('giro');
        $this->authorizeGiroAccess($dokumen->giro);

        $reconciliation = BankReconciliation::query()
            ->where('dokumen_id', $dokumen->id)
            ->first();

        if ($reconciliation !== null && $reconciliation->isLockedForEditing()) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete this statement because its bank reconciliation is locked (pending validation or completed).');
        }

        $storedFilename = $dokumen->getRawOriginal('filename1');
        if ($storedFilename) {
            $filePath = public_path('dokumens/'.$storedFilename);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $dokumen->delete();

        return redirect()->back()->with('success', 'Bank statement deleted successfully.');
    }

    private function getGiros(array $userRoles)
    {
        if (array_intersect($this->allowedRoles, $userRoles)) {
            return Giro::orderBy('bank_id', 'asc')->get();
        }

        return Giro::where('project', auth()->user()->project)->orderBy('bank_id', 'asc')->get();
    }

    private function uploadFile($file, Giro $giro): string
    {
        $extension = $file->getClientOriginalExtension();
        $accountNumber = Str::slug($giro->acc_no, '_');
        $filename = 'koran_'.$accountNumber.'_'.rand().'.'.$extension;
        $file->move(public_path('dokumens'), $filename);

        return $filename;
    }

    public function check_koran_files($year): array
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect($this->allowedRoles, $userRoles)) {
            $giros = Giro::with('bank')->orderBy('bank_id', 'asc')->get();
        } else {
            $giros = Giro::with('bank')->where('project', auth()->user()->project)->orderBy('bank_id', 'asc')->get();
        }

        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $result = [];
        $year_data = [];

        $reconciliationIndex = BankReconciliation::query()
            ->whereIn('giro_id', $giros->pluck('id')->all())
            ->whereYear('periode', $year)
            ->get(['id', 'giro_id', 'periode', 'status', 'validation_status'])
            ->keyBy(function (BankReconciliation $row): string {
                return $row->giro_id.'_'.Carbon::parse($row->periode)->format('m');
            });

        foreach ($giros as $giro) {
            $korans = Dokumen::query()
                ->with('createdBy')
                ->where('type', 'koran')
                ->where('giro_id', $giro->id)
                ->whereYear('periode', $year)
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->getRawOriginal('periode'))->format('m');
                });

            $completed_count = 0;
            $giro_data = array_map(function ($month) use ($korans, &$completed_count, $giro, $reconciliationIndex, $year) {
                $koran = $korans->get($month);
                $has_file = $koran && $koran->getRawOriginal('filename1') !== null;
                if ($has_file) {
                    $completed_count++;
                }

                $reconciliation = $reconciliationIndex->get($giro->id.'_'.$month);

                return [
                    'month' => $month,
                    'month_label' => Carbon::createFromDate((int) $year, (int) $month, 1)->format('F'),
                    'periode' => $year.'-'.$month,
                    'status' => $has_file,
                    'filename1' => $koran ? $koran->filename1 : null,
                    'upload_date' => $koran && $koran->created_at ? Carbon::parse($koran->created_at)->format('d M Y') : null,
                    'uploaded_by' => $koran ? $koran->created_by_name : null,
                    'dokumen_id' => $koran ? $koran->id : null,
                    'giro_project' => $giro->project,
                    'reconciliation_id' => $reconciliation?->id,
                    'reconciliation_status' => $reconciliation?->status,
                    'reconciliation_validation_status' => $reconciliation?->validation_status,
                ];
            }, $months);

            $year_data[] = [
                'giro_id' => $giro->id,
                'acc_no' => $giro->acc_no,
                'acc_name' => $giro->acc_name,
                'project' => $giro->project,
                'bank_name' => $giro->bank ? $giro->bank->name : 'N/A',
                'completed_count' => $completed_count,
                'total_months' => 12,
                'completion_percentage' => round(($completed_count / 12) * 100, 1),
                'acc_name_full' => $giro->acc_no.' - '.$giro->acc_name.' - '.$giro->project,
                'data' => $giro_data,
            ];
        }

        $result[] = [
            'year' => $year,
            'giros' => $year_data,
        ];

        return $result;
    }

    private function calculateStatistics(array $korans): array
    {
        $totalAccounts = 0;
        $totalMonths = 0;
        $completedMonths = 0;

        foreach ($korans as $koran) {
            foreach ($koran['giros'] as $giro) {
                $totalAccounts++;
                foreach ($giro['data'] as $month) {
                    $totalMonths++;
                    if ($month['status']) {
                        $completedMonths++;
                    }
                }
            }
        }

        $completionPercentage = $totalMonths > 0 ? round(($completedMonths / $totalMonths) * 100, 1) : 0;
        $missingMonths = $totalMonths - $completedMonths;

        return [
            'total_accounts' => $totalAccounts,
            'total_months' => $totalMonths,
            'completed_months' => $completedMonths,
            'missing_months' => $missingMonths,
            'completion_percentage' => $completionPercentage,
        ];
    }

    public function data()
    {
        $dokumens = Dokumen::where('type', 'koran')->orderBy('periode', 'desc')->get();

        return datatables()->of($dokumens)
            ->editColumn('created_by', function ($dokumen) {
                return $dokumen->created_by_name;
            })
            ->addColumn('account', function ($dokumen) {
                return $dokumen->giro_id === null
                    ? '<small>No Giro Assigned</small>'
                    : '<small>'.$dokumen->giro->acc_no.' - '.$dokumen->giro->acc_name.'</small>';
            })
            ->addColumn('account_project', function ($dokumen) {
                return $dokumen->giro_id === null
                    ? '<small>No Project Assigned</small>'
                    : $dokumen->giro->project;
            })
            ->addColumn('reconciled', function ($dokumen) {
                return $dokumen->filename2 !== null && $dokumen->verified_by !== null
                    ? '<i class="fas fa-check" style="color: green;"></i>'
                    : '<i class="fas fa-times" style="color: red;"></i>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.koran.action')
            ->rawColumns(['action', 'account', 'reconciled'])
            ->toJson();
    }

    protected function authorizeGiroAccess(?Giro $giro): void
    {
        abort_if($giro === null, 404);

        $userRoles = app(UserController::class)->getUserRoles();
        if (array_intersect($this->allowedRoles, $userRoles)) {
            return;
        }

        abort_unless($giro->project === auth()->user()->project, 403);
    }
}
