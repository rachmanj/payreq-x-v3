<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Dokumen;
use App\Models\Giro;
use Illuminate\Http\Request;

class CashierDokumenController extends Controller
{
    public function index()
    {
        $page = request()->query('page', 'index');
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['admin', 'superadmin'], $userRoles)) {
            $giros = Giro::all();
        } else {
            $giros = Giro::where('project', auth()->user()->project)->get();
        }

        return view('cashier.dokumen.' . $page, compact('giros'));
    }

    public function upload(Request $request)
    {
        $this->validate($request, [
            'file_upload' => 'required|mimes:pdf',
        ]);

        $file = $request->file('file_upload');
        if ($request->type == 'koran') {
            $filename = 'koran_' . rand();
        } else {
            $filename = 'pcbc_' . rand();
        }
        $file->move(public_path('file_upload'), $filename);

        Dokumen::create([
            'filename1' => $filename,
            'giro_id' => $request->giro_id,
            'type' => $request->type,
            'project' => $request->project,
            'periode' => $request->periode ? $request->periode . '-01' : null,
            'dokumen_date' => $request->dokumen_date,
            'remarks' => $request->remarks,
            'created_by' => auth()->user()->id,
        ]);

        return redirect()->back()->with('success', $request->type == 'koran' ? 'Koran uploaded successfully.' : 'PCBC uploaded successfully.');
    }

    public function update(Request $request, $id)
    {
        return $request->all();
        $dokumen = Dokumen::findOrFail($id);

        if ($request->hasFile('file_upload')) {
            // Remove the old file from the server
            $oldFilePath = public_path('file_upload/' . $dokumen->filename1);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }

            // Upload the new file
            $file = $request->file('file_upload');
            if ($request->type == 'koran') {
                $filename = 'koran_' . rand() . '_' . $file->getClientOriginalName();
            } else {
                $filename = 'pcbc_' . rand() . '_' . $file->getClientOriginalName();
            }
            $file->move(public_path('file_upload'), $filename);
            $dokumen->filename1 = $filename;
        }

        // Update the record
        $dokumen->giro_id = $request->giro_id;
        $dokumen->type = $request->type;
        $dokumen->project = $request->project;
        $dokumen->periode = $request->periode ? $request->periode . '-01' : null;
        $dokumen->dokumen_date = $request->dokumen_date;
        $dokumen->remarks = $request->remarks;
        $dokumen->save();

        return redirect()->back()->with('success', 'Document updated successfully.');
    }

    public function destroy($id)
    {
        $dokumen = Dokumen::findOrFail($id);

        // Remove the file from the server
        $filePath = public_path('file_upload/' . $dokumen->filename1);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete the record from the database
        $dokumen->delete();

        return redirect()->back()->with('success', 'Document deleted successfully.');
    }

    public function data()
    {
        $type = request()->query('type');

        $dokumens = Dokumen::where('type', $type)->orderBy('periode', 'desc')->get();

        return datatables()->of($dokumens)
            ->editColumn('created_by', function ($dokumen) {
                return $dokumen->created_by_name;
            })
            ->addColumn('account', function ($dokumen) {
                if ($dokumen->giro_id === null) {
                    return '<small>No Giro Assigned</small>';
                }
                return '<small>' . $dokumen->giro->acc_no . ' - ' . $dokumen->giro->acc_name . '</small>';
            })
            ->addColumn('account_project', function ($dokumen) {
                if ($dokumen->giro_id === null) {
                    return '<small>No Project Assigned</small>';
                }
                return $dokumen->giro->project;
            })
            ->addColumn('reconciled', function ($dokumen) {
                return $dokumen->filename2 !== null && $dokumen->verified_by !== null
                    ? '<i class="fas fa-check" style="color: green;"></i>'
                    : '<i class="fas fa-times" style="color: red;"></i>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.dokumen.action')
            ->rawColumns(['action', 'account', 'reconciled'])
            ->toJson();
    }
}
