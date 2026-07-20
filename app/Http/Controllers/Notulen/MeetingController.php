<?php

namespace App\Http\Controllers\Notulen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notulen\StoreMeetingRequest;
use App\Jobs\ProcessMeeting;
use App\Models\Meeting;
use App\Services\Notulen\RetrievalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:akses_notulen')->only(['index', 'show', 'data', 'download']);
        $this->middleware('permission:upload_notulen')->only(['store']);
        $this->middleware('permission:delete_notulen')->only(['destroy']);
    }

    public function index()
    {
        return view('notulen.meetings.index');
    }

    public function data()
    {
        $query = Meeting::query()
            ->with('uploader')
            ->orderByDesc('created_at');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('meeting_date_fmt', fn (Meeting $m) => $m->meeting_date?->format('d-M-Y') ?? '-')
            ->addColumn('status_badge', function (Meeting $m) {
                return match ($m->status) {
                    Meeting::STATUS_PROCESSED => '<span class="badge badge-success">Processed</span>',
                    Meeting::STATUS_FAILED => '<span class="badge badge-danger">Failed</span>',
                    Meeting::STATUS_PROCESSING => '<span class="badge badge-info">Processing</span>',
                    default => '<span class="badge badge-warning">Pending</span>',
                };
            })
            ->addColumn('uploader_name', fn (Meeting $m) => e($m->uploader->name ?? '-'))
            ->addColumn('action', function (Meeting $m) {
                $show = route('notulen.meetings.show', $m);
                $actions = '<a href="'.$show.'" class="btn btn-info btn-xs mr-1">view</a>';

                if (Auth::user()?->can('delete_notulen')) {
                    $actions .= '<form method="POST" action="'.route('notulen.meetings.destroy', $m).'" class="d-inline" onsubmit="return confirm(\'Hapus dokumen ini?\');">'
                        .csrf_field()
                        .method_field('DELETE')
                        .'<button type="submit" class="btn btn-danger btn-xs">delete</button>'
                        .'</form>';
                }

                return $actions;
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function store(StoreMeetingRequest $request)
    {
        $file = $request->file('file');
        $fileHash = hash_file('sha256', $file->getRealPath());

        $duplicate = Meeting::query()
            ->where('file_hash', $fileHash)
            ->whereIn('status', [Meeting::STATUS_PROCESSED, Meeting::STATUS_PENDING, Meeting::STATUS_PROCESSING])
            ->latest('id')
            ->first();

        if ($duplicate) {
            return redirect()
                ->route('notulen.meetings.show', $duplicate)
                ->with('warning', 'File PDF yang sama sudah pernah diunggah ('.$duplicate->title.'). Upload dibatalkan untuk menghindari re-embedding.');
        }

        $disk = Storage::disk('notulen');
        $storedName = Str::uuid()->toString().'.pdf';
        $relativePath = $disk->putFileAs('', $file, $storedName);

        $meeting = Meeting::query()->create([
            'title' => $request->validated('title'),
            'meeting_date' => $request->validated('meeting_date'),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'file_hash' => $fileHash,
            'status' => Meeting::STATUS_PENDING,
            'uploaded_by' => Auth::id(),
        ]);

        ProcessMeeting::dispatch($meeting);

        return redirect()
            ->route('notulen.meetings.index')
            ->with('success', 'Notulen berhasil diunggah. Sedang diproses.');
    }

    public function reprocess(Meeting $meeting)
    {
        $meeting->update([
            'status' => Meeting::STATUS_PENDING,
            'full_text' => null,
            'error_message' => null,
        ]);

        $meeting->chunks()->delete();

        ProcessMeeting::dispatch($meeting);

        return redirect()
            ->route('notulen.meetings.show', $meeting)
            ->with('success', 'Notulen sedang diproses ulang.');
    }

    public function show(Meeting $meeting)
    {
        $meeting->load('uploader');

        return view('notulen.meetings.show', compact('meeting'));
    }

    public function download(Request $request, Meeting $meeting)
    {
        if (! $request->hasValidSignature() && ! Auth::check()) {
            abort(403);
        }

        if (Auth::check() && ! Auth::user()->can('akses_notulen')) {
            abort(403);
        }

        $disk = Storage::disk('notulen');

        if (! $disk->exists($meeting->file_path)) {
            abort(404);
        }

        return $disk->download($meeting->file_path, $meeting->original_filename);
    }

    public function destroy(Meeting $meeting)
    {
        $disk = Storage::disk('notulen');
        if ($disk->exists($meeting->file_path)) {
            $disk->delete($meeting->file_path);
        }

        $meeting->delete();
        RetrievalService::clearChunkCache();

        return redirect()
            ->route('notulen.meetings.index')
            ->with('success', 'Notulen dihapus.');
    }
}
