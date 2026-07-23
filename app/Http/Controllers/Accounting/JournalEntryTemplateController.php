<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalEntryTemplateRequest;
use App\Models\Department;
use App\Models\JournalEntryTemplate;
use App\Models\JournalEntryTemplateLine;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class JournalEntryTemplateController extends Controller
{
    public function index()
    {
        $templates = JournalEntryTemplate::with('createdBy')->withCount('lines')->orderBy('name')->get();

        return view('accounting.journal-entries.templates.index', compact('templates'));
    }

    public function create()
    {
        $projects = Project::orderBy('code')->get();
        $departments = Department::orderBy('department_name')->get();

        return view('accounting.journal-entries.templates.create', [
            'template' => null,
            'projects' => $projects,
            'departments' => $departments,
        ]);
    }

    public function store(StoreJournalEntryTemplateRequest $request)
    {
        $template = DB::transaction(function () use ($request) {
            $template = JournalEntryTemplate::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => auth()->id(),
            ]);

            $this->syncLines($template, $request->lines);

            return $template;
        });

        return redirect()
            ->route('accounting.journal-entries.templates.index')
            ->with('success', 'Template "'.$template->name.'" saved successfully.');
    }

    public function edit(int $id)
    {
        $template = JournalEntryTemplate::with('lines')->findOrFail($id);
        $projects = Project::orderBy('code')->get();
        $departments = Department::orderBy('department_name')->get();

        return view('accounting.journal-entries.templates.create', compact('template', 'projects', 'departments'));
    }

    public function update(StoreJournalEntryTemplateRequest $request, int $id)
    {
        $template = JournalEntryTemplate::findOrFail($id);

        DB::transaction(function () use ($request, $template) {
            $template->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $template->lines()->delete();
            $this->syncLines($template, $request->lines);
        });

        return redirect()
            ->route('accounting.journal-entries.templates.index')
            ->with('success', 'Template "'.$template->name.'" updated successfully.');
    }

    public function destroy(int $id)
    {
        $template = JournalEntryTemplate::findOrFail($id);
        $name = $template->name;
        $template->delete();

        return redirect()
            ->route('accounting.journal-entries.templates.index')
            ->with('success', 'Template "'.$name.'" deleted successfully.');
    }

    public function lines(int $id)
    {
        $template = JournalEntryTemplate::with('lines')->findOrFail($id);

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'lines' => $template->lines->map(fn ($line) => [
                'account_code' => $line->account_code,
                'debit_credit' => $line->debit_credit,
                'default_amount' => $line->default_amount,
                'project' => $line->project,
                'cost_center' => $line->cost_center,
                'description' => $line->description,
            ]),
        ]);
    }

    protected function syncLines(JournalEntryTemplate $template, array $lines): void
    {
        foreach ($lines as $index => $line) {
            JournalEntryTemplateLine::create([
                'journal_entry_template_id' => $template->id,
                'line_no' => $index + 1,
                'account_code' => $line['account_code'],
                'debit_credit' => $line['debit_credit'],
                'default_amount' => $line['default_amount'] ?? null,
                'project' => $line['project'] ?? null,
                'cost_center' => $line['cost_center'] ?? null,
                'description' => $line['description'] ?? null,
            ]);
        }
    }
}
