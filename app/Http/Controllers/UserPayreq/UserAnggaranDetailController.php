<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\Controller;
use App\Models\Anggaran;
use App\Models\AnggaranDetail;
use App\Services\AnggaranReleaseService;
use Illuminate\Http\RedirectResponse;

class UserAnggaranDetailController extends Controller
{
    public function __construct(
        protected AnggaranReleaseService $releaseService
    ) {}

    public function destroy(Anggaran $anggaran, AnggaranDetail $detail): RedirectResponse
    {
        $this->authorize('editThroughPayreq', $anggaran);

        if ((int) $detail->anggaran_id !== (int) $anggaran->id) {
            abort(404);
        }

        $detail->delete();
        $this->releaseService->forgetDetailCaches((int) $anggaran->id);

        return redirect()->back()->with('success', 'Budget line removed.');
    }
}
