<?php

namespace App\Services\Sap;

use App\Models\Project;
use App\Services\SapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapProjectSyncService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function syncProjects(): array
    {
        try {
            $this->sapService->ensureSession();

            $response = $this->sapService->getProjects();
            
            if (!isset($response) && !is_array($response)) {
                throw new \Exception('Invalid response format from SAP ProjectsService_GetProjectList');
            }
            
            $projects = is_array($response) ? $response : [];
            
            $stats = [
                'total' => count($projects),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_messages' => [],
            ];

            DB::beginTransaction();

            try {
                foreach ($projects as $sapProject) {
                    try {
                        $projectCode = $sapProject['ProjectCode'] ?? $sapProject['Code'] ?? null;
                        $projectName = $sapProject['ProjectName'] ?? $sapProject['Name'] ?? null;

                        if (!$projectCode || !$projectName) {
                            $stats['errors']++;
                            $stats['error_messages'][] = 'Missing ProjectCode or ProjectName: ' . json_encode($sapProject);
                            continue;
                        }

                        $project = Project::where('sap_code', $projectCode)->first();

                        if ($project) {
                            $project->update([
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Description'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'synced_at' => now(),
                            ]);
                            $stats['updated']++;
                        } else {
                            Project::create([
                                'code' => $projectCode,
                                'sap_code' => $projectCode,
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Description'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'is_selectable' => true,
                                'synced_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $stats['error_messages'][] = "Error processing project {$projectCode}: " . $e->getMessage();
                        Log::error('Error syncing SAP project', [
                            'project' => $sapProject,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('SAP Projects sync completed', $stats);

                return [
                    'success' => true,
                    'message' => "Sync completed: {$stats['created']} created, {$stats['updated']} updated, {$stats['errors']} errors",
                    'stats' => $stats,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('SAP Projects sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'stats' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => 1,
                    'error_messages' => [$e->getMessage()],
                ],
            ];
        }
    }
}

