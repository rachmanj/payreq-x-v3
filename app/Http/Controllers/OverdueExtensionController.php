<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewOverdueExtensionRequest;
use App\Http\Requests\StoreOverdueExtensionRequest;
use App\Models\OverdueExtension;
use App\Models\Payreq;
use App\Models\Realization;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OverdueExtensionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:approve_overdue_extension')->only(['index', 'data']);
    }

    public function index(): View
    {
        return view('document-overdue.extensions.index');
    }

    public function data()
    {
        $query = OverdueExtension::query()
            ->with(['requestor', 'reviewer'])
            ->orderByDesc('created_at');

        return datatables()->eloquent($query)
            ->addIndexColumn()
            ->addColumn('employee', function (OverdueExtension $extension) {
                return e($extension->requestor->name);
            })
            ->addColumn('document_label', function (OverdueExtension $extension) {
                $typeLabel = $extension->document_type === OverdueExtension::DOCUMENT_PAYREQ ? 'Payreq' : 'Realization';

                return $typeLabel.' — '.e($extension->resolveNomor() ?? '—');
            })
            ->addColumn('project', function (OverdueExtension $extension) {
                return e($extension->resolveProject() ?? '—');
            })
            ->editColumn('current_due_date', function (OverdueExtension $extension) {
                return $extension->current_due_date?->format('d-M-Y') ?? '—';
            })
            ->editColumn('requested_due_date', function (OverdueExtension $extension) {
                return $extension->requested_due_date?->format('d-M-Y') ?? '—';
            })
            ->editColumn('reason', function (OverdueExtension $extension) {
                return '<span title="'.e($extension->reason).'">'.e(\Illuminate\Support\Str::limit($extension->reason, 80)).'</span>';
            })
            ->addColumn('extension_seq', function (OverdueExtension $extension) {
                $seq = $extension->extensionSequence();

                return $seq['index'].' / '.$seq['total'];
            })
            ->editColumn('created_at', function (OverdueExtension $extension) {
                return $extension->created_at?->timezone(config('app.timezone'))->format('d-M-Y H:i') ?? '—';
            })
            ->editColumn('status', function (OverdueExtension $extension) {
                return match ($extension->status) {
                    OverdueExtension::STATUS_PENDING => '<span class="badge badge-warning">pending</span>',
                    OverdueExtension::STATUS_APPROVED => '<span class="badge badge-success">approved</span>',
                    OverdueExtension::STATUS_REJECTED => '<span class="badge badge-danger">rejected</span>',
                    default => e($extension->status),
                };
            })
            ->addColumn('action', function (OverdueExtension $extension) {
                return view('document-overdue.extensions.action', ['extension' => $extension])->render();
            })
            ->rawColumns(['reason', 'status', 'action'])
            ->toJson();
    }

    public function store(StoreOverdueExtensionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $documentType = $validated['document_type'];
        $documentId = (int) $validated['document_id'];

        if ($documentType === OverdueExtension::DOCUMENT_PAYREQ) {
            /** @var Payreq $document */
            $document = Payreq::query()->findOrFail($documentId);

            abort_if($document->user_id !== auth()->id(), 403);

            abort_if(! in_array($document->project, OverdueExtension::eligibleProjects(), true), 403);

            if (! (
                $document->type === 'advance'
                    && $document->status === 'paid'
                    && $document->due_date
                    && Carbon::parse($document->due_date)->lt(now())
            )) {
                throw ValidationException::withMessages([
                    'document_id' => ['This payment request is not eligible for an overdue extension request.'],
                ]);
            }

            $currentDueDate = Carbon::parse($document->due_date)->toDateString();
        } else {
            /** @var Realization $document */
            $document = Realization::query()->findOrFail($documentId);

            abort_if($document->user_id !== auth()->id(), 403);

            abort_if(! in_array($document->project, OverdueExtension::eligibleProjects(), true), 403);

            if (! (
                $document->status === 'approved'
                    && $document->due_date
                    && Carbon::parse($document->due_date)->lt(now())
            )) {
                throw ValidationException::withMessages([
                    'document_id' => ['This realization is not eligible for an overdue extension request.'],
                ]);
            }

            $currentDueDate = Carbon::parse($document->due_date)->toDateString();
        }

        if (Carbon::parse($validated['requested_due_date'])->lte(Carbon::parse($currentDueDate))) {
            return redirect()->back()->withInput()->withErrors([
                'requested_due_date' => 'The requested due date must be after the current due date.',
            ]);
        }

        $pendingExists = OverdueExtension::query()
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->where('status', OverdueExtension::STATUS_PENDING)
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'document_id' => ['There is already a pending extension request for this document.'],
            ]);
        }

        OverdueExtension::query()->create([
            'document_type' => $documentType,
            'document_id' => $documentId,
            'user_id' => auth()->id(),
            'current_due_date' => $currentDueDate,
            'requested_due_date' => Carbon::parse($validated['requested_due_date'])->toDateString(),
            'reason' => $validated['reason'],
            'status' => OverdueExtension::STATUS_PENDING,
        ]);

        return redirect()->route('user-payreqs.index')->with('success', 'Overdue extension request submitted.');
    }

    public function approve(OverdueExtension $extension): RedirectResponse
    {
        abort_unless(auth()->user()?->can('approve_overdue_extension'), 403);

        abort_if($extension->status !== OverdueExtension::STATUS_PENDING, 422);

        DB::transaction(function () use ($extension) {
            $extension->update([
                'status' => OverdueExtension::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => null,
            ]);

            if ($extension->document_type === OverdueExtension::DOCUMENT_PAYREQ) {
                Payreq::query()->whereKey($extension->document_id)->update([
                    'due_date' => $extension->requested_due_date->toDateString(),
                ]);
            } elseif ($extension->document_type === OverdueExtension::DOCUMENT_REALIZATION) {
                Realization::query()->whereKey($extension->document_id)->update([
                    'due_date' => $extension->requested_due_date->toDateString(),
                ]);
            }
        });

        return redirect()->route('document-overdue.extensions.index')->with('success', 'Extension approved.');
    }

    public function reject(ReviewOverdueExtensionRequest $request, OverdueExtension $extension): RedirectResponse
    {
        abort_if($extension->status !== OverdueExtension::STATUS_PENDING, 422);

        $extension->update([
            'status' => OverdueExtension::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->validated()['review_notes'],
        ]);

        return redirect()->route('document-overdue.extensions.index')->with('success', 'Extension rejected.');
    }
}
