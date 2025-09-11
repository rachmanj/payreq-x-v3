<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Bilyet;
use App\Models\BilyetAudit;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BilyetReportController extends Controller
{
    public function index()
    {
        return view('cashier.bilyets.reports.index');
    }

    public function dashboard(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());
        $project = $request->get('project');

        $query = Bilyet::query();

        if ($project) {
            $query->where('project', $project);
        }

        $bilyets = $query->whereBetween('created_at', [$dateFrom, $dateTo])->get();

        // Status distribution
        $statusDistribution = $bilyets->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'percentage' => 0 // Will be calculated
            ];
        });

        $totalAmount = $bilyets->sum('amount');
        $statusDistribution = $statusDistribution->map(function ($data) use ($totalAmount) {
            $data['percentage'] = $totalAmount > 0 ? round(($data['total_amount'] / $totalAmount) * 100, 2) : 0;
            return $data;
        });

        // Type distribution
        $typeDistribution = $bilyets->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount')
            ];
        });

        // Bank distribution
        $bankDistribution = $bilyets->load('giro.bank')->groupBy('giro.bank.name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount')
            ];
        });

        // Monthly trends
        $monthlyTrends = Bilyet::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->whereBetween('created_at', [now()->subMonths(12), now()])
            ->when($project, function ($query) use ($project) {
                return $query->where('project', $project);
            })
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // User activity
        $userActivity = BilyetAudit::with('user')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('COUNT(*) as activity_count'))
            ->groupBy('user_id')
            ->orderBy('activity_count', 'desc')
            ->limit(10)
            ->get();

        // Performance metrics
        $performanceMetrics = [
            'total_bilyets' => $bilyets->count(),
            'total_amount' => $totalAmount,
            'average_amount' => $bilyets->count() > 0 ? $bilyets->avg('amount') : 0,
            'onhand_count' => $bilyets->where('status', 'onhand')->count(),
            'released_count' => $bilyets->where('status', 'release')->count(),
            'settled_count' => $bilyets->where('status', 'cair')->count(),
            'voided_count' => $bilyets->where('status', 'void')->count(),
        ];

        return response()->json([
            'status_distribution' => $statusDistribution,
            'type_distribution' => $typeDistribution,
            'bank_distribution' => $bankDistribution,
            'monthly_trends' => $monthlyTrends,
            'user_activity' => $userActivity,
            'performance_metrics' => $performanceMetrics,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ]);
    }

    public function export(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());
        $project = $request->get('project');
        $format = $request->get('format', 'excel');

        $query = Bilyet::with(['giro.bank', 'creator', 'audits.user']);

        if ($project) {
            $query->where('project', $project);
        }

        $bilyets = $query->whereBetween('created_at', [$dateFrom, $dateTo])->get();

        if ($format === 'excel') {
            return $this->exportToExcel($bilyets, $dateFrom, $dateTo);
        } elseif ($format === 'pdf') {
            return $this->exportToPdf($bilyets, $dateFrom, $dateTo);
        }

        return response()->json(['error' => 'Invalid format'], 400);
    }

    private function exportToExcel($bilyets, $dateFrom, $dateTo)
    {
        $filename = 'bilyet_report_' . $dateFrom . '_to_' . $dateTo . '.xlsx';

        // This would use Laravel Excel package
        // For now, return CSV format
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($bilyets) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Bilyet Number',
                'Bank',
                'Type',
                'Status',
                'Amount',
                'Bilyet Date',
                'Cair Date',
                'Created By',
                'Created At',
                'Project'
            ]);

            // Data
            foreach ($bilyets as $bilyet) {
                fputcsv($file, [
                    $bilyet->full_nomor,
                    $bilyet->giro->bank->name ?? 'N/A',
                    $bilyet->type_label,
                    $bilyet->status_label,
                    $bilyet->amount,
                    $bilyet->bilyet_date?->format('Y-m-d'),
                    $bilyet->cair_date?->format('Y-m-d'),
                    $bilyet->creator->name ?? 'Unknown',
                    $bilyet->created_at->format('Y-m-d H:i:s'),
                    $bilyet->project ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($bilyets, $dateFrom, $dateTo)
    {
        // This would use a PDF library like DomPDF or TCPDF
        // For now, return a simple HTML view
        return view('cashier.bilyets.reports.pdf', compact('bilyets', 'dateFrom', 'dateTo'));
    }

    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subMonths(6));
        $dateTo = $request->get('date_to', now());
        $project = $request->get('project');

        // Processing time analytics
        $processingTimes = Bilyet::select(
            'id',
            'created_at',
            'cair_date',
            'status',
            DB::raw('DATEDIFF(COALESCE(cair_date, NOW()), created_at) as days_to_process')
        )
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($project, function ($query) use ($project) {
                return $query->where('project', $project);
            })
            ->get();

        $avgProcessingTime = $processingTimes->where('status', 'cair')->avg('days_to_process');

        // Status transition analytics
        $statusTransitions = BilyetAudit::where('action', 'status_changed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('old_values', 'new_values', 'created_at')
            ->get()
            ->map(function ($audit) {
                $oldStatus = json_decode($audit->old_values, true)['status'] ?? 'unknown';
                $newStatus = json_decode($audit->new_values, true)['status'] ?? 'unknown';
                return [
                    'transition' => $oldStatus . ' → ' . $newStatus,
                    'date' => $audit->created_at
                ];
            })
            ->groupBy('transition')
            ->map(function ($group) {
                return $group->count();
            });

        // Volume trends by day
        $dailyVolume = Bilyet::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($project, function ($query) use ($project) {
                return $query->where('project', $project);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'processing_times' => [
                'average_days' => round($avgProcessingTime, 2),
                'distribution' => $processingTimes->groupBy(function ($item) {
                    if ($item->days_to_process <= 1) return 'Same Day';
                    if ($item->days_to_process <= 7) return 'Within Week';
                    if ($item->days_to_process <= 30) return 'Within Month';
                    return 'Over Month';
                })->map->count()
            ],
            'status_transitions' => $statusTransitions,
            'daily_volume' => $dailyVolume,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ]);
    }

    public function auditReport(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());
        $userId = $request->get('user_id');

        $query = BilyetAudit::with(['bilyet.giro.bank', 'user'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $audits = $query->get();

        // Audit summary
        $auditSummary = $audits->groupBy('action')->map(function ($group) {
            return [
                'count' => $group->count(),
                'percentage' => 0 // Will be calculated
            ];
        });

        $totalAudits = $audits->count();
        $auditSummary = $auditSummary->map(function ($data) use ($totalAudits) {
            $data['percentage'] = $totalAudits > 0 ? round(($data['count'] / $totalAudits) * 100, 2) : 0;
            return $data;
        });

        // User activity summary
        $userActivity = $audits->groupBy('user.name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'actions' => $group->groupBy('action')->map->count()
            ];
        });

        return response()->json([
            'audit_summary' => $auditSummary,
            'user_activity' => $userActivity,
            'total_audits' => $totalAudits,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ]);
    }
}
