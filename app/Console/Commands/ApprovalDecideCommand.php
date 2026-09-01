<?php

namespace App\Console\Commands;

use App\Models\ApprovalPlan;
use App\Models\User;
use App\Services\ApprovalDecisionService;
use Illuminate\Console\Command;

class ApprovalDecideCommand extends Command
{
    protected $signature = 'approval:decide
                            {plan_id : ID approval plan yang akan diputuskan}
                            {--decision=approve : Keputusan: approve, revise, atau reject}
                            {--remarks= : Catatan singkat (opsional)}';

    protected $description = 'Putuskan approval plan atas nama Iwan (rachmanj), identik dengan aksi UI';

    public function handle(ApprovalDecisionService $approvalDecisionService): int
    {
        $decision = strtolower((string) $this->option('decision'));
        $statusMap = [
            'approve' => 1,
            'revise' => 2,
            'reject' => 3,
        ];

        if (! array_key_exists($decision, $statusMap)) {
            $this->error('Keputusan tidak valid. Gunakan: approve, revise, atau reject.');

            return self::FAILURE;
        }

        $plan = ApprovalPlan::find($this->argument('plan_id'));
        if ($plan === null) {
            $this->error('Approval plan tidak ditemukan.');

            return self::FAILURE;
        }

        if ((int) $plan->status !== 0 || (int) $plan->is_open !== 1) {
            $this->error('Approval plan sudah diputuskan / ditutup.');

            return self::FAILURE;
        }

        $actor = User::where('username', 'rachmanj')->firstOrFail();
        $remarks = $this->option('remarks');
        $remarks = filled($remarks) ? (string) $remarks : null;

        $result = $approvalDecisionService->decide(
            $plan,
            $statusMap[$decision],
            $remarks,
            $actor
        );

        $this->info(sprintf(
            'Plan #%d | %s #%d | keputusan: %s | agregat (setuju:%d, revisi:%d, tolak:%d / %d plan)',
            $plan->id,
            $result['document_type'],
            $result['document_id'],
            $decision,
            $result['approved_count'],
            $result['revised_count'],
            $result['rejected_count'],
            $result['total_open_plans'],
        ));

        $this->info('Status dokumen: '.($result['document_status'] ?? 'n/a'));
        $this->info($result['message']);

        return self::SUCCESS;
    }
}
