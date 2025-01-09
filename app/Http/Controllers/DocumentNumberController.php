<?php

namespace App\Http\Controllers;

use App\Models\DocumentNumber;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class DocumentNumberController extends Controller
{
    public $document_types = ['payreq', 'realization', 'cash-journal', 'verification-journal', 'eom-journal', 'pcbc', 'draft', 'rab', 'delivery'];

    public function index()
    {
        $projects = Project::orderBy('code', 'asc')->get();
        $document_types = $this->document_types;

        return view('document-number.index', compact(['projects', 'document_types']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required',
            'project' => 'required',
            'year' => 'required',
        ]);

        // check for duplication
        $document_number = DocumentNumber::where('document_type', $request->document_type)
            ->where('project', $request->project)
            ->where('year', $request->year)
            ->first();

        if ($document_number) {
            return redirect()->route('document-number.index')->with('error', 'Document number already exists.');
        }

        // create
        DocumentNumber::create([
            'document_type' => $request->document_type,
            'project' => $request->project,
            'year' => $request->year,
        ]);

        return redirect()->route('document-number.index')->with('success', 'Document number created successfully.');
    }

    public function auto_generate(Request $request)
    {
        $request->validate([
            'year' => 'required',
        ]);

        $projects = Project::get();
        // $document_types = ['payreq'];
        $document_types = $this->document_types;

        foreach ($projects as $project) {
            foreach ($document_types as $document_type) {
                // check for duplication
                $document_number = DocumentNumber::where('document_type', $document_type)
                    ->where('project', $project->code)
                    ->where('year', $request->year)
                    ->first();

                if ($document_number) {
                    continue;
                }

                DocumentNumber::create([
                    'document_type' => $document_type,
                    'project' => $project->code,
                    'year' => $request->year,
                ]);
            }
        }

        return redirect()->route('document-number.index')->with('success', 'Document number created successfully.');
    }

    public function destroy(DocumentNumber $document_number)
    {
        $document_number->delete();

        return redirect()->route('document-number.index')->with('success', 'Document number deleted successfully.');
    }

    public function data()
    {
        $documents = DocumentNumber::orderBy('year', 'desc')->orderBy('project', 'desc')->limit(100)->get();

        return datatables()->of($documents)
            ->addIndexColumn()
            ->addColumn('action', 'document-number.action')
            ->rawColumns(['action'])
            ->toJson();
    }

    public function generate_document_number($document_type, $project)
    {
        $year = date('Y');

        $document = DocumentNumber::where('document_type', $document_type)
            ->where('project', $project)
            ->where('year', $year)
            ->first();

        if (!$document) {
            return 'n/a';
        }

        $document->increment('last_number');
        $document_code = $this->document_code($document_type);

        // $nomor = Carbon::now()->format('y') . $document_code . substr(auth()->user()->project, 1, 2)  . str_pad($document->last_number, 5, '0', STR_PAD_LEFT);
        $nomor = Carbon::now()->format('y') . $document_code . substr($project, 1, 2)  . str_pad($document->last_number, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generate_draft_document_number($project)
    {
        $year = date('Y');

        $document = DocumentNumber::where('document_type', 'draft')
            ->where('project', $project)
            ->where('year', $year)
            ->first();

        if (!$document) {
            return 'n/a';
        }

        $document->increment('last_number');

        $nomor = Carbon::now()->format('y') . 'Q' . substr(auth()->user()->project, 1, 2)  . str_pad($document->last_number, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }


    public function document_code($document_type)
    {
        $documents = [
            'payreq' => '01',
            'realization' => '02',
            'rab' => '03',
            'cash-journal' => '05',
            'verification-journal' => '06',
            'delivery' => '07',
            'eom-journal' => '08',
            'pcbc' => '09',
        ];

        return $documents[$document_type];
    }
}
