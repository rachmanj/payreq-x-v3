<?php

namespace App\Services;

use App\Models\Bilyet;
use App\Models\BilyetTemp;
use App\Models\Giro;
use App\Http\Controllers\UserController;
use App\Events\BilyetUpdated;
use Illuminate\Support\Facades\DB;

class BilyetService
{
    /**
     * Get dashboard data for bilyets
     */
    public function getDashboardData($userRoles, $project = null)
    {
        $query = Bilyet::with(['giro.bank'])
            ->selectRaw('
                giro_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = "onhand" THEN 1 ELSE 0 END) as onhand,
                SUM(CASE WHEN status = "release" THEN 1 ELSE 0 END) as release,
                SUM(CASE WHEN status = "cair" THEN 1 ELSE 0 END) as cair,
                SUM(CASE WHEN status = "void" THEN 1 ELSE 0 END) as void,
                SUM(CASE WHEN status IN ("release", "cair") THEN amount ELSE 0 END) as amount
            ')
            ->groupBy('giro_id');

        if (!in_array('admin', $userRoles) && !in_array('superadmin', $userRoles)) {
            $query->where('project', $project);
        }

        return $query->get();
    }

    /**
     * Process bulk update of bilyets
     */
    public function processBulkUpdate($bilyetIds, $data)
    {
        $bilyets = Bilyet::whereIn('id', $bilyetIds)
            ->where('status', 'onhand') // Only update onhand bilyets
            ->get();

        $updatedCount = 0;
        foreach ($bilyets as $bilyet) {
            $oldValues = $bilyet->toArray();
            $newValues = [
                'bilyet_date' => $data['bilyet_date'],
                'amount' => $data['amount'],
                'remarks' => $data['remarks'],
                'status' => 'release', // Always set to release for bulk update
            ];

            $bilyet->update($newValues);

            // Fire event for audit trail
            event(new BilyetUpdated($bilyet, auth()->user(), $oldValues, $newValues, 'bulk_updated'));

            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Validate and process import data
     */
    public function validateImportData($tempData)
    {
        $errors = [];
        $warnings = [];

        foreach ($tempData as $index => $item) {
            $rowNumber = $index + 2; // Excel row number (accounting for header)

            // Validate required fields
            if (empty($item->prefix)) {
                $errors[] = "Row {$rowNumber}: Prefix is required";
            }

            if (empty($item->nomor)) {
                $errors[] = "Row {$rowNumber}: Nomor is required";
            }

            if (empty($item->type)) {
                $errors[] = "Row {$rowNumber}: Type is required";
            } elseif (!in_array($item->type, Bilyet::TYPES)) {
                $errors[] = "Row {$rowNumber}: Invalid type '{$item->type}'. Must be one of: " . implode(', ', Bilyet::TYPES);
            }

            // Validate giro_id
            if (empty($item->giro_id)) {
                $errors[] = "Row {$rowNumber}: Bank account not found for '{$item->acc_no}'";
            }

            // Validate dates
            if ($item->bilyet_date && !$this->isValidDate($item->bilyet_date)) {
                $errors[] = "Row {$rowNumber}: Invalid bilyet_date format";
            }

            if ($item->cair_date && !$this->isValidDate($item->cair_date)) {
                $errors[] = "Row {$rowNumber}: Invalid cair_date format";
            }

            // Validate amount
            if ($item->amount && (!is_numeric($item->amount) || $item->amount < 0)) {
                $errors[] = "Row {$rowNumber}: Amount must be a positive number";
            }

            // Check for duplicates
            if ($this->isDuplicateInTemp($item->prefix, $item->nomor, $item->id)) {
                $warnings[] = "Row {$rowNumber}: Duplicate bilyet number '{$item->prefix}{$item->nomor}' in import data";
            }

            // Check if already exists in main table
            if ($this->existsInMainTable($item->prefix, $item->nomor)) {
                $warnings[] = "Row {$rowNumber}: Bilyet '{$item->prefix}{$item->nomor}' already exists in system";
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'valid' => empty($errors)
        ];
    }

    /**
     * Import validated data from temp table
     */
    public function importFromTemp($receiveDate, $userId)
    {
        $bilyets = BilyetTemp::where('created_by', $userId)->get();
        $importedCount = 0;

        DB::transaction(function () use ($bilyets, $receiveDate, &$importedCount) {
            foreach ($bilyets as $bilyet) {
                $status = $this->determineStatusFromTemp($bilyet);

                Bilyet::create([
                    'giro_id' => $bilyet->giro_id,
                    'prefix' => $bilyet->prefix,
                    'nomor' => $bilyet->nomor,
                    'type' => $bilyet->type,
                    'receive_date' => $receiveDate,
                    'bilyet_date' => $bilyet->bilyet_date,
                    'cair_date' => $bilyet->cair_date,
                    'amount' => $bilyet->amount,
                    'remarks' => $bilyet->remarks,
                    'loan_id' => $bilyet->loan_id,
                    'status' => $status,
                    'created_by' => $bilyet->created_by,
                    'project' => $bilyet->project,
                ]);

                $importedCount++;
            }

            // Delete temp data after successful import
            BilyetTemp::where('created_by', auth()->id())->delete();
        });

        return $importedCount;
    }

    /**
     * Get filtered bilyets for DataTable
     */
    public function getFilteredBilyets($filters, $userRoles, $project = null)
    {
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
        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applyGiroFilter($query, $filters['giro_id'] ?? null);
        $this->applyNomorFilter($query, $filters['nomor'] ?? null);
        $this->applyDateFilter($query, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $this->applyAmountFilter($query, $filters['amount_from'] ?? null, $filters['amount_to'] ?? null);

        // Role-based access control
        if (!in_array('superadmin', $userRoles) && !in_array('admin', $userRoles)) {
            $query->where('project', $project);
        }

        return $query->orderBy('bilyet_date', 'asc')->get();
    }

    /**
     * Get statistics for selected bilyets
     */
    public function getSelectedStatistics($bilyetIds)
    {
        $bilyets = Bilyet::whereIn('id', $bilyetIds)->get();

        $totalAmount = $bilyets->sum('amount');
        $count = $bilyets->count();
        $statusCount = $bilyets->groupBy('status')->map->count();
        $typeCount = $bilyets->groupBy('type')->map->count();

        return [
            'count' => $count,
            'total_amount' => $totalAmount,
            'average_amount' => $count > 0 ? $totalAmount / $count : 0,
            'status_breakdown' => $statusCount,
            'type_breakdown' => $typeCount,
            'amount_range' => $this->getAmountRange($bilyets->pluck('amount')->filter())
        ];
    }

    // Private helper methods

    private function determineStatusFromTemp($bilyet)
    {
        if ($bilyet->amount && $bilyet->bilyet_date && $bilyet->cair_date) {
            return 'cair';
        } else {
            return $bilyet->amount || $bilyet->bilyet_date ? 'release' : 'onhand';
        }
    }

    private function isValidDate($date)
    {
        return \DateTime::createFromFormat('Y-m-d', $date) !== false;
    }

    private function isDuplicateInTemp($prefix, $nomor, $excludeId = null)
    {
        $query = BilyetTemp::where('prefix', $prefix)->where('nomor', $nomor);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function existsInMainTable($prefix, $nomor)
    {
        return Bilyet::where('prefix', $prefix)->where('nomor', $nomor)->exists();
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

    private function applyAmountFilter($query, $amountFrom, $amountTo)
    {
        if ($amountFrom && $amountTo) {
            $query->whereBetween('amount', [$amountFrom, $amountTo]);
        } elseif ($amountFrom) {
            $query->where('amount', '>=', $amountFrom);
        } elseif ($amountTo) {
            $query->where('amount', '<=', $amountTo);
        }
    }

    private function getAmountRange($amounts)
    {
        if ($amounts->isEmpty()) {
            return null;
        }

        return [
            'min' => $amounts->min(),
            'max' => $amounts->max(),
            'is_same' => $amounts->min() === $amounts->max()
        ];
    }
}
