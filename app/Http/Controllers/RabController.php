<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Payreq;
use App\Models\Rab;
use Illuminate\Http\Request;

class RabController extends Controller
{
    public function index()
    {
        $projects = ['000H', '001H', '017C', '021C', '022C', '023C', 'APS'];
        $departments = Department::orderBy('department_name', 'asc')->get();

        return view('rabs.index', compact('projects', 'departments'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'rab_no'        => 'required|unique:rabs',
            'date'          => 'required',
            'description'   => 'required',
            'project_code'  => 'required',
            'department_id' => 'required',
            'budget'        => 'required',
        ]);

        if ($request->file_upload) {
            $file = $request->file('file_upload');
            $filename = rand() . '_' . $file->getClientOriginalName();
            $file->move(public_path('document_upload'), $filename);
        } else {
            $filename = null;
        }

        $rab = new Rab();
        $rab->rab_no = $request->rab_no;
        $rab->date = $request->date;
        $rab->description = $request->description;
        $rab->project_code = $request->project_code;
        $rab->department_id = $request->department_id;
        $rab->budget = $request->budget;
        $rab->filename = $filename;
        $rab->status = 'progress';
        $rab->created_by = auth()->user()->id;
        $rab->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Created RAB', $request->rab_no);

        return redirect()->route('rabs.index')->with('success', 'RAB created successfully');
    }

    public function edit($id)
    {
        $rab = Rab::find($id);
        $projects = ['000H', '001H', '017C', '021C', '022C', '023C', 'APS'];
        $departments = Department::orderBy('department_name', 'asc')->get();

        return view('rabs.edit', compact('rab', 'projects', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'rab_no'    => 'required|unique:rabs,rab_no,' . $id,
            'date'  => 'required',
            'description' => 'required',
            'project_code' => 'required',
            'department_id' => 'required',
            'budget' => 'required',
        ]);


        $rab = Rab::find($id);
        $rab->rab_no = $request->rab_no;
        $rab->date = $request->date;
        $rab->description = $request->description;
        $rab->project_code = $request->project_code;
        $rab->department_id = $request->department_id;
        $rab->budget = $request->budget;
        $rab->status = $request->status;

        if ($request->file_upload) {
            $file = $request->file('file_upload');
            $filename = rand() . '-' . $file->getClientOriginalName();
            $file->move(public_path('document_upload'), $filename);
            $rab->filename = $filename;
        }

        $rab->save();

        return redirect()->route('rabs.index')->with('success', 'RAB updated successfully');
    }

    public function show($id)
    {
        $rab = Rab::find($id);
        $advances = Payreq::where('rab_id', $id)->whereNotNull('outgoing_date')
            ->whereNull('realization_amount')
            ->sum('payreq_idr');
        $realizations = Payreq::where('rab_id', $id)->whereNotNull('realization_amount')
            ->sum('realization_amount');
        $total_release = $advances + $realizations;
        $progress = app(ToolController::class)->progress($id);
        $status_color = app(ToolController::class)->statusColor($progress);

        return view('rabs.show', compact('rab', 'total_release', 'progress', 'status_color'));
    }

    public function update_status(Request $request, $id)
    {
        $rab = Rab::find($id);

        $rab->status = $request->status;
        $rab->save();

        return redirect()->route('rabs.index')->with('success', 'RAB Status updated successfully');
    }

    public function destroy($id)
    {
        $rab = Rab::find($id);
        if ($rab->payreqs->count() > 0) {
            return redirect()->route('rabs.index')->with('error', 'RAB cannot be deleted because it has payreqs');
        }

        $rab->delete();

        return redirect()->route('rabs.index')->with('success', 'RAB deleted successfully');
    }

    public function data()
    {
        $rabs = Rab::orderBy('date', 'desc')->orderBy('rab_no', 'desc')->get();

        return datatables()->of($rabs)
            ->editColumn('rab_no', function ($rab) {
                if ($rab->status == 'progress')
                    $status_badge = 'primary';
                elseif ($rab->status == 'finish')
                    $status_badge = 'success';
                elseif ($rab->status == 'cancel')
                    $status_badge = 'danger';
                else
                    $status_badge = 'warning';

                return '<a href="' . route('rabs.show', $rab->id) . '">' . $rab->rab_no . '</a> <br> <button class="btn btn-xs btn-' . $status_badge . '" style="pointer-events: none;">' . ucfirst($rab->status) . '</button>';
            })
            ->editColumn('date', function ($rab) {
                return date('d-M-Y', strtotime($rab->date));
            })
            ->editColumn('project_code', function ($rab) {
                return $rab->project_code . ' | ' . $rab->department->akronim;
            })
            ->editColumn('budget', function ($rab) {
                return number_format($rab->budget, 2);
            })
            ->editColumn('advance', function ($rab) {
                $payreq = Payreq::where('rab_id', $rab->id)
                    ->whereNotNull('outgoing_date')
                    ->whereNull('realization_date');
                return number_format($payreq->sum('payreq_idr'), 2);
            })
            ->editColumn('realization', function ($rab) {
                $payreq = Payreq::where('rab_id', $rab->id)
                    ->whereNotNull('realization_date');
                return number_format($payreq->sum('realization_amount'), 2);
            })
            ->addColumn('progress', function ($rab) {
                // $payreqs = Payreq::where('rab_id', $rab->id)->get();
                // $total_advance = $payreqs->whereNotNull('outgoing_date')->whereNull('realization_date')->sum('payreq_idr');
                // $total_realization = $payreqs->whereNotNull('realization_date')->sum('realization_amount');
                // $total_release = $total_advance + $total_realization;
                // $progress = ($total_release / $rab->budget) * 100;
                $progress = app(ToolController::class)->progress($rab->id);

                $status = '';
                if ($progress == 100) {
                    $status = 'success';
                } elseif ($progress > 0 && $progress < 100) {
                    $status = 'warning';
                } else {
                    $status = 'danger';
                }

                return '<div class="progress">
                            <div class="progress-bar progress-bar-striped bg-' . $status . '" role="progressbar" style="width: ' . $progress . '%" aria-valuenow="' . $progress . '" aria-valuemin="0" aria-valuemax="100">' . number_format($progress, 2) . '%</div>
                        </div>';
                // return number_format($progress, 2) . '%';
            })
            ->addIndexColumn()
            ->addColumn('action', 'rabs.action')
            ->rawColumns(['action', 'progress', 'rab_no'])
            ->toJson();
    }

    public function payreq_data($rab_id)
    {
        $payreqs = Payreq::where('rab_id', $rab_id)->whereNotNull('outgoing_date')
            ->orderBy('approve_date', 'desc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('approve_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->approve_date));
            })
            ->editColumn('employee', function ($payreq) {
                return $payreq->employee->name;
            })
            ->editColumn('amount', function ($payreq) {
                if ($payreq->realization_amount != null) {
                    return number_format($payreq->realization_amount, 2);
                } else {
                    return number_format($payreq->payreq_idr, 2);
                }
            })
            ->addIndexColumn()
            ->toJson();
    }

    public function test($id)
    {
        $result = app(ToolController::class)->progress($id);
        return $result;
        die;
    }
}
