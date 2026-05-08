<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_match_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->string('match_type', 32);
            $table->float('confidence_score')->nullable();
            $table->decimal('bank_total', 18, 2)->default(0);
            $table->decimal('sap_total', 18, 2)->default(0);
            $table->decimal('difference', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('bank_reconciliation_id');
        });

        Schema::create('match_group_bank_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_match_group_id')->constrained('reconciliation_match_groups')->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->constrained('bank_statement_lines')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('bank_statement_line_id');
            $table->unique(['reconciliation_match_group_id', 'bank_statement_line_id'], 'mg_bank_group_line_unique');
        });

        Schema::create('match_group_sap_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_match_group_id')->constrained('reconciliation_match_groups')->cascadeOnDelete();
            $table->foreignId('sap_gl_line_id')->constrained('sap_gl_lines')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('sap_gl_line_id');
            $table->unique(['reconciliation_match_group_id', 'sap_gl_line_id'], 'mg_sap_group_line_unique');
        });

        if (Schema::hasTable('reconciliation_matches')) {
            $matches = DB::table('reconciliation_matches')->orderBy('id')->get();

            foreach ($matches as $match) {
                $bankNet = 0.0;
                $sapNet = 0.0;

                if ($match->bank_statement_line_id) {
                    $b = DB::table('bank_statement_lines')->where('id', $match->bank_statement_line_id)->first();
                    if ($b) {
                        $bankNet = (float) $b->debit - (float) $b->credit;
                    }
                }

                if ($match->sap_gl_line_id) {
                    $s = DB::table('sap_gl_lines')->where('id', $match->sap_gl_line_id)->first();
                    if ($s) {
                        $sapNet = (float) $s->debit - (float) $s->credit;
                    }
                }

                $diff = round($bankNet - $sapNet, 2);

                $groupId = DB::table('reconciliation_match_groups')->insertGetId([
                    'bank_reconciliation_id' => $match->bank_reconciliation_id,
                    'match_type' => $match->match_type,
                    'confidence_score' => $match->confidence_score,
                    'bank_total' => number_format($bankNet, 2, '.', ''),
                    'sap_total' => number_format($sapNet, 2, '.', ''),
                    'difference' => number_format($diff, 2, '.', ''),
                    'notes' => $match->notes,
                    'created_by' => $match->created_by,
                    'created_at' => $match->created_at,
                    'updated_at' => $match->updated_at,
                ]);

                if ($match->bank_statement_line_id) {
                    DB::table('match_group_bank_lines')->insert([
                        'reconciliation_match_group_id' => $groupId,
                        'bank_statement_line_id' => $match->bank_statement_line_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($match->sap_gl_line_id) {
                    DB::table('match_group_sap_lines')->insert([
                        'reconciliation_match_group_id' => $groupId,
                        'sap_gl_line_id' => $match->sap_gl_line_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::dropIfExists('reconciliation_matches');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('match_group_sap_lines');
        Schema::dropIfExists('match_group_bank_lines');
        Schema::dropIfExists('reconciliation_match_groups');

        if (! Schema::hasTable('reconciliation_matches')) {
            Schema::create('reconciliation_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
                $table->foreignId('bank_statement_line_id')->nullable()->constrained('bank_statement_lines')->cascadeOnDelete();
                $table->foreignId('sap_gl_line_id')->nullable()->constrained('sap_gl_lines')->cascadeOnDelete();
                $table->string('match_type', 32);
                $table->float('confidence_score')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('bank_reconciliation_id');
            });
        }
    }
};
