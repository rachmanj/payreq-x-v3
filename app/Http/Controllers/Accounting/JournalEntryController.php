<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\Account;
use App\Models\Department;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\JournalEntryTemplate;
use App\Models\Project;
use App\Models\SapSubmissionLog;
use App\Services\JournalEntrySubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    public function __construct(
        protected JournalEntrySubmissionService $submissionService
    ) {}

    public function index()
    {
        return view('accounting.journal-entries.index');
    }

    public function data()
    {
        $entries = JournalEntry::with(['createdBy', 'sapSubmittedBy'])
            ->orderByDesc('created_at')
            ->get();

        return datatables()->of($entries)
            ->addIndexColumn()
            ->editColumn('date', fn (JournalEntry $entry) => $entry->date?->format('d-M-Y'))
            ->editColumn('memo', fn (JournalEntry $entry) => \Illuminate\Support\Str::limit($entry->memo ?? '', 60))
            ->addColumn('status_badge', function (JournalEntry $entry) {
                if ($entry->isReversed()) {
                    return '<span class="badge badge-secondary">Reversed</span>';
                }

                return match ($entry->sap_submission_status) {
                    'success' => '<span class="badge badge-success">Posted</span>',
                    'failed' => '<span class="badge badge-danger">Failed</span>',
                    default => '<span class="badge badge-warning">Draft</span>',
                };
            })
            ->addColumn('sap_journal_no', fn (JournalEntry $entry) => $entry->sap_journal_no ?? '<span class="text-muted">—</span>')
            ->addColumn('created_by_name', fn (JournalEntry $entry) => $entry->createdBy?->name ?? 'N/A')
            ->addColumn('action', 'accounting.journal-entries.action')
            ->rawColumns(['status_badge', 'sap_journal_no', 'action'])
            ->toJson();
    }

    public function create()
    {
        $templates = JournalEntryTemplate::orderBy('name')->get();
        $projects = Project::orderBy('code')->get();
        $departments = Department::orderBy('department_name')->get();

        return view('accounting.journal-entries.create', compact('templates', 'projects', 'departments'));
    }

    public function store(StoreJournalEntryRequest $request)
    {
        $journalEntry = DB::transaction(function () use ($request) {
            $entry = JournalEntry::create([
                'number' => 'TEMP',
                'date' => $request->date,
                'memo' => $request->memo,
                'reference' => $request->reference,
                'journal_entry_template_id' => $request->journal_entry_template_id,
                'created_by' => auth()->id(),
                'sap_submission_status' => 'pending',
            ]);

            $entry->update([
                'number' => 'JE-'.str_pad((string) $entry->id, 6, '0', STR_PAD_LEFT),
            ]);

            $this->syncLines($entry, $request->lines);

            return $entry->fresh();
        });

        return redirect()
            ->route('accounting.journal-entries.show', $journalEntry->id)
            ->with('success', 'Journal entry '.$journalEntry->number.' created successfully.');
    }

    public function show(int $id)
    {
        $journalEntry = JournalEntry::with(['lines', 'createdBy', 'sapSubmittedBy', 'sapReversedBy', 'template'])
            ->findOrFail($id);

        $submissionLogs = SapSubmissionLog::where('journal_entry_id', $journalEntry->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $accountNames = Account::whereIn('account_number', $journalEntry->lines->pluck('account_code'))
            ->pluck('account_name', 'account_number');

        return view('accounting.journal-entries.show', compact('journalEntry', 'submissionLogs', 'accountNames'));
    }

    public function edit(int $id)
    {
        $journalEntry = JournalEntry::with('lines')->findOrFail($id);

        if (! $journalEntry->isEditable()) {
            return redirect()
                ->route('accounting.journal-entries.show', $journalEntry->id)
                ->with('error', 'This journal entry cannot be edited because it has already been posted to SAP.');
        }

        $templates = JournalEntryTemplate::orderBy('name')->get();
        $projects = Project::orderBy('code')->get();
        $departments = Department::orderBy('department_name')->get();

        return view('accounting.journal-entries.edit', compact('journalEntry', 'templates', 'projects', 'departments'));
    }

    public function update(StoreJournalEntryRequest $request, int $id)
    {
        $journalEntry = JournalEntry::findOrFail($id);

        if (! $journalEntry->isEditable()) {
            return redirect()
                ->route('accounting.journal-entries.show', $journalEntry->id)
                ->with('error', 'This journal entry cannot be edited because it has already been posted to SAP.');
        }

        DB::transaction(function () use ($request, $journalEntry) {
            $journalEntry->update([
                'date' => $request->date,
                'memo' => $request->memo,
                'reference' => $request->reference,
                'journal_entry_template_id' => $request->journal_entry_template_id,
            ]);

            $journalEntry->lines()->delete();
            $this->syncLines($journalEntry, $request->lines);
        });

        return redirect()
            ->route('accounting.journal-entries.show', $journalEntry->id)
            ->with('success', 'Journal entry updated successfully.');
    }

    public function destroy(int $id)
    {
        $journalEntry = JournalEntry::findOrFail($id);

        if (! $journalEntry->isEditable()) {
            return redirect()
                ->route('accounting.journal-entries.index')
                ->with('error', 'Only draft journal entries can be deleted.');
        }

        $journalEntry->delete();

        return redirect()
            ->route('accounting.journal-entries.index')
            ->with('success', 'Journal entry deleted successfully.');
    }

    public function print(int $id)
    {
        $journalEntry = JournalEntry::with(['lines', 'createdBy', 'sapSubmittedBy'])->findOrFail($id);

        $accountNames = Account::whereIn('account_number', $journalEntry->lines->pluck('account_code'))
            ->pluck('account_name', 'account_number');

        return view('accounting.journal-entries.print', compact('journalEntry', 'accountNames'));
    }

    public function submitToSap(int $id)
    {
        $journalEntry = JournalEntry::findOrFail($id);
        $user = auth()->user();

        if (! $this->canSubmitToSap($user)) {
            return redirect()
                ->route('accounting.journal-entries.show', $journalEntry->id)
                ->with('error', 'You do not have permission to submit to SAP B1.');
        }

        if ($journalEntry->isPosted()) {
            return redirect()
                ->route('accounting.journal-entries.show', $journalEntry->id)
                ->with('error', 'This journal has already been submitted to SAP B1.');
        }

        $result = $this->submissionService->submit($journalEntry, $user);

        return redirect()
            ->route('accounting.journal-entries.show', $journalEntry->id)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function reverseToSap(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $journalEntry = JournalEntry::findOrFail($id);
        $user = auth()->user();

        if (! $user->can('cancel_sap_journal')) {
            return redirect()
                ->route('accounting.journal-entries.show', $journalEntry->id)
                ->with('error', 'You do not have permission to reverse journals in SAP B1.');
        }

        $result = $this->submissionService->reverse($journalEntry, $user, $request->reason);

        return redirect()
            ->route('accounting.journal-entries.show', $journalEntry->id)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    protected function canSubmitToSap($user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'cashier', 'approver']);
    }

    protected function syncLines(JournalEntry $journalEntry, array $lines): void
    {
        foreach ($lines as $index => $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'line_no' => $index + 1,
                'account_code' => $line['account_code'],
                'debit_credit' => $line['debit_credit'],
                'amount' => $line['amount'],
                'project' => $line['project'] ?? null,
                'cost_center' => $line['cost_center'] ?? null,
                'description' => $line['description'] ?? null,
            ]);
        }
    }
}
