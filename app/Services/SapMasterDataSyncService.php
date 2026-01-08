<?php

namespace App\Services;

use App\Models\SapAccount;
use App\Models\SapBusinessPartner;
use App\Models\SapCostCenter;
use App\Models\SapProject;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapMasterDataSyncService
{
    public function __construct(
        protected SapService $sapService
    ) {
    }

    public function syncProjects(): array
    {
        $records = $this->sapService->getProjects();

        return $this->upsertRecords(
            SapProject::class,
            $records,
            function (array $record) {
                return [
                    'code' => $record['Code'] ?? null,
                    'name' => $record['Name'] ?? null,
                    'status' => $record['Status'] ?? null,
                    'active' => ($record['Active'] ?? 'tYES') !== 'tNO',
                    'start_date' => $this->toDate($record['StartDate'] ?? null),
                    'end_date' => $this->toDate($record['EndDate'] ?? null),
                    'project_manager' => $record['ProjectManager'] ?? null,
                    'metadata' => $record,
                ];
            }
        );
    }

    public function syncCostCenters(): array
    {
        $records = $this->sapService->getCostCenters();

        return $this->upsertRecords(
            SapCostCenter::class,
            $records,
            function (array $record) {
                return [
                    'code' => $record['CenterCode'] ?? null,
                    'name' => $record['CenterName'] ?? null,
                    'segment' => $record['GroupCode'] ?? null,
                    'department' => $record['Department'] ?? null,
                    'active' => ($record['Active'] ?? 'tYES') !== 'tNO',
                    'metadata' => $record,
                ];
            }
        );
    }

    public function syncAccounts(): array
    {
        $records = $this->sapService->getAccounts();

        return $this->upsertRecords(
            SapAccount::class,
            $records,
            function (array $record) {
                return [
                    'code' => $record['Code'] ?? null,
                    'name' => $record['Name'] ?? null,
                    'account_type' => $record['AccountType'] ?? null,
                    'category' => $record['AccountCategory'] ?? null,
                    'postable' => ($record['Postable'] ?? 'tYES') !== 'tNO',
                    'active' => ($record['ActiveAccount'] ?? 'tYES') !== 'tNO',
                    'metadata' => $record,
                ];
            }
        );
    }

    public function syncBusinessPartners(): array
    {
        $records = $this->sapService->getBusinessPartners();

        return $this->upsertRecords(
            SapBusinessPartner::class,
            $records,
            function (array $record) {
                $existing = SapBusinessPartner::where('code', $record['CardCode'] ?? null)->first();
                
                $payload = [
                    'code' => $record['CardCode'] ?? null,
                    'name' => $record['CardName'] ?? null,
                    'type' => $record['CardType'] ?? null,
                    'active' => ($record['Active'] ?? 'tYES') !== 'tNO',
                    'vat_liable' => ($record['VatLiable'] ?? 'tYES') !== 'tNO',
                    'federal_tax_id' => $record['FederalTaxID'] ?? null,
                    'phone' => $record['Phone1'] ?? null,
                    'email' => $record['Email'] ?? null,
                    'address' => $record['Address'] ?? null,
                    'credit_limit' => $this->toDecimal($record['CreditLimit'] ?? null),
                    'balance' => $this->toDecimal($record['Balance'] ?? null),
                    'metadata' => $record,
                ];

                if ($existing) {
                    if ($existing->name !== $payload['name']) {
                        $payload['previous_name'] = $existing->name;
                        $payload['name_changed_at'] = Carbon::now();
                    }
                    
                    if ($existing->active !== $payload['active']) {
                        $payload['previous_active'] = $existing->active;
                        $payload['status_changed_at'] = Carbon::now();
                    }
                }

                return $payload;
            }
        );
    }

    public function syncAll(): array
    {
        return [
            'projects' => $this->syncProjects(),
            'cost_centers' => $this->syncCostCenters(),
            'accounts' => $this->syncAccounts(),
            'business_partners' => $this->syncBusinessPartners(),
        ];
    }

    protected function upsertRecords(string $modelClass, array $records, callable $map): array
    {
        $synced = 0;
        $errors = [];

        collect($records)
            ->filter(fn ($record) => filled(data_get($record, 'Code')) || filled(data_get($record, 'CenterCode')) || filled(data_get($record, 'CardCode')))
            ->chunk(100)
            ->each(function (Collection $chunk) use ($modelClass, $map, &$synced, &$errors) {
                DB::beginTransaction();
                try {
                    foreach ($chunk as $record) {
                        $payload = $map($record);
                        $payload['last_synced_at'] = Carbon::now();

                        $modelClass::updateOrCreate(
                            ['code' => $payload['code']],
                            $payload
                        );

                        $synced++;
                    }
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $errors[] = $e->getMessage();
                    Log::error('SAP master data sync error', [
                        'model' => $modelClass,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    protected function toDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function toDecimal($value): ?float
    {
        if (blank($value)) {
            return null;
        }

        try {
            return (float) $value;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

