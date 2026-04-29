<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealizationAttachmentRequest;
use App\Models\Realization;
use App\Models\RealizationAttachment;
use App\Services\RealizationAttachmentsAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class RealizationAttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:akses_realization_attachments')->only([
            'index', 'show', 'data', 'download',
        ]);
        $this->middleware('permission:create_realization_attachments')->only(['store']);
        $this->middleware('permission:delete_realization_attachments')->only(['destroy']);
    }

    public function index(Request $request, RealizationAttachmentsAccessService $accessService)
    {
        $projects = $accessService->allowedProjectCodesForFilters($request->user());
        $creators = $accessService->creatorUsersForFilters($request->user());

        return view('cashier.realization-attachments.index', [
            'filterProjects' => $projects,
            'filterCreators' => $creators,
            'filters' => [
                'project' => $request->query('project'),
                'document_search' => $request->query('document_search'),
                'creator_user_id' => $request->query('creator_user_id'),
            ],
        ]);
    }

    public function data(Request $request, RealizationAttachmentsAccessService $accessService)
    {
        foreach (['project', 'document_search', 'creator_user_id'] as $field) {
            if ($request->has($field) && $request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        $user = $request->user();

        $allowedProjects = $accessService->allowedProjectCodesForFilters($user);

        if ($allowedProjects === []) {
            $projectRules = ['nullable', 'prohibited'];
        } else {
            $projectRules = ['nullable', 'string', Rule::in($allowedProjects)];
        }

        $validated = $request->validate([
            'project' => $projectRules,
            'document_search' => ['nullable', 'string', 'max:100'],
            'creator_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($validated['creator_user_id']) && ! $accessService->creatorIdIsAllowedForFilters($user, (int) $validated['creator_user_id'])) {
            abort(403);
        }

        $query = Realization::query()
            ->select('realizations.*')
            ->join('payreqs', 'payreqs.id', '=', 'realizations.payreq_id')
            ->withCount('attachments')
            ->with(['requestor', 'payreq']);

        $accessService->applyScopeToRealizationsQuery($query, $user);

        if (! empty($validated['project'])) {
            $query->where('realizations.project', $validated['project']);
        }

        if (! empty($validated['document_search'])) {
            $term = '%'.$validated['document_search'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('realizations.nomor', 'like', $term)
                    ->orWhere('payreqs.nomor', 'like', $term);
            });
        }

        if (! empty($validated['creator_user_id'])) {
            $creatorId = (int) $validated['creator_user_id'];
            $query->where(function ($q) use ($creatorId): void {
                $q->where('realizations.user_id', $creatorId)
                    ->orWhere('payreqs.user_id', $creatorId);
            });
        }

        $query->orderByDesc('realizations.created_at');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('realization_no', fn (Realization $r) => e($r->nomor))
            ->addColumn('realization_date', fn (Realization $r) => $r->created_at ? $r->created_at->format('d-M-Y') : '')
            ->addColumn('payreq_no', fn (Realization $r) => e($r->payreq->nomor ?? ''))
            ->addColumn('employee_name', fn (Realization $r) => e(optional($r->requestor)->name ?? ''))
            ->addColumn('project', fn (Realization $r) => e((string) ($r->project ?? '')))
            ->addColumn('action', function (Realization $r) {
                $url = route('cashier.realization-attachments.show', $r);
                $count = (int) ($r->attachments_count ?? 0);
                $badge = $count > 0
                    ? '<span class="badge badge-secondary mr-1 align-middle" title="Attachments">'.$count.'</span>'
                    : '';

                return $badge.'<a href="'.$url.'" class="btn btn-warning btn-xs">open</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function show(Realization $realization, RealizationAttachmentsAccessService $accessService)
    {
        abort_if(! $accessService->userCanViewRealization(Auth::user(), $realization), 403);

        $realization->load([
            'attachments.creator',
            'payreq.requestor',
            'requestor',
        ]);

        return view('cashier.realization-attachments.show', compact('realization'));
    }

    public function store(
        StoreRealizationAttachmentRequest $request,
        Realization $realization,
        RealizationAttachmentsAccessService $accessService
    ) {
        abort_if(! $accessService->userCanViewRealization(Auth::user(), $realization), 403);

        $file = $request->file('file');
        $disk = Storage::disk('realization_attachments');
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $storedName = Str::uuid()->toString().'.'.$extension;
        $relativePath = $disk->putFileAs((string) $realization->id, $file, $storedName);

        RealizationAttachment::create([
            'realization_id' => $realization->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $relativePath,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('cashier.realization-attachments.show', $realization)
            ->with('success', 'File uploaded.');
    }

    public function download(
        Request $request,
        RealizationAttachment $attachment,
        RealizationAttachmentsAccessService $accessService
    ) {
        $attachment->load('realization');

        abort_if(! $accessService->userCanViewRealization($request->user(), $attachment->realization), 403);

        $disk = Storage::disk('realization_attachments');

        if (! $disk->exists($attachment->stored_path)) {
            abort(404);
        }

        return $disk->download($attachment->stored_path, $attachment->original_name);
    }

    public function destroy(
        Request $request,
        RealizationAttachment $attachment,
        RealizationAttachmentsAccessService $accessService
    ) {
        $attachment->load('realization');

        abort_if(! $accessService->userCanViewRealization($request->user(), $attachment->realization), 403);

        abort_if((int) $attachment->created_by !== (int) $request->user()->id, 403);

        $disk = Storage::disk('realization_attachments');
        if ($disk->exists($attachment->stored_path)) {
            $disk->delete($attachment->stored_path);
        }

        $realization = $attachment->realization;
        $attachment->delete();

        return redirect()
            ->route('cashier.realization-attachments.show', $realization)
            ->with('success', 'Attachment deleted.');
    }
}
