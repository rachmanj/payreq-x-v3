<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyTransferProofJob;
use App\Models\Outgoing;
use App\Models\OutgoingAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OutgoingAttachmentController extends Controller
{
    public function store(Request $request, Outgoing $outgoing)
    {
        abort_if($outgoing->payment_method !== 'transfer', 403);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $file = $request->file('file');
        $disk = Storage::disk('outgoing_attachments');
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $storedName = Str::uuid()->toString().'.'.$extension;
        $relativePath = $disk->putFileAs((string) $outgoing->id, $file, $storedName);

        $attachment = OutgoingAttachment::create([
            'outgoing_id' => $outgoing->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $relativePath,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_by' => Auth::id(),
        ]);

        $mime = (string) ($attachment->mime ?? '');
        if ($mime !== '' && (str_starts_with($mime, 'image/') || $mime === 'application/pdf')) {
            VerifyTransferProofJob::dispatch($attachment);
        }

        return redirect()->back()->with('success', 'Bukti transfer berhasil diunggah.');
    }

    public function download(OutgoingAttachment $attachment)
    {
        $disk = Storage::disk('outgoing_attachments');

        if (! $disk->exists($attachment->stored_path)) {
            abort(404);
        }

        return $disk->download($attachment->stored_path, $attachment->original_name);
    }

    public function destroy(OutgoingAttachment $attachment)
    {
        abort_if((int) $attachment->created_by !== (int) Auth::id(), 403);

        $disk = Storage::disk('outgoing_attachments');
        if ($disk->exists($attachment->stored_path)) {
            $disk->delete($attachment->stored_path);
        }

        $attachment->delete();

        return redirect()->back()->with('success', 'Bukti transfer berhasil dihapus.');
    }

    public function reverify(OutgoingAttachment $attachment)
    {
        $attachment->update([
            'verification_status' => 'pending',
            'verification_result' => null,
        ]);

        VerifyTransferProofJob::dispatch($attachment);

        return redirect()->back()->with('success', 'Verifikasi bukti transfer dijalankan ulang.');
    }
}
