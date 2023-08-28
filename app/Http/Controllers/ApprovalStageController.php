<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStage;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalStageController extends Controller
{
    public function index()
    {
        $approvers = User::role('approver')->select('id', 'name')->get();
        $projects = ['000H', '001H', '017C', '021C', '022C', '023C'];
        $departments = Department::orderBy('department_name', 'asc')->get();

        return view('approval-stages.index', compact('approvers', 'projects', 'departments'));
    }

    public function store(Request $request)
    {
        // return $request->all();
        // die;

        $request->validate([
            'approver_id' => 'required',
            'project' => 'required',
            'departments' => 'required',
            'documents' => 'required',
        ]);

        $departments = Department::whereIn('id', $request->departments)->get();

        // foreach ($departments as $department) {
        //     ApprovalStage::create([
        //         'department_id' => $department->id,
        //         'approver_id' => $request->approver_id,
        //         'project' => $request->project,
        //     ]);
        // }

        // $stage = new ApprovalStage();
        foreach ($departments as $department) {
            foreach ($request->documents as $document) {
                ApprovalStage::create([
                    'department_id' => $department->id,
                    'approver_id' => $request->approver_id,
                    'project' => $request->project,
                    'document_type' => $document,
                ]);
            }
        }

        return redirect()->route('approval-stages.index')->with('success', 'Approval stage created successfully.');
    }

    public function destroy($id)
    {
        ApprovalStage::destroy($id);
        return response()->json(['success' => 'Approval stage deleted successfully.']);
    }

    public function data()
    {
        // list of users where has role approver
        $approvers = User::role('approver')->select('id', 'name')
            ->whereHas('approval_stages')
            ->get();

        return datatables()->of($approvers)
            ->addColumn('approver', function ($approver) {
                return $approver->name;
            })
            ->addColumn('stages', function ($approver) {
                // $stages = ApprovalStage::select('project', 'department_id', 'approver_id')
                //         ->where('approver_id', $approver->id)
                //         ->groupBy(['department_id', 'project', 'approver_id'])
                //         ->get();

                $stages = ApprovalStage::where('approver_id', $approver->id)->orderBy('department_id', 'asc')->get();
                $html = '<ul class="list-group">';

                foreach ($stages as $stage) {
                    // $stage_documents = ApprovalStage::where('approver_id', $approver->id)
                    //     ->where('department_id', $stage->department_id)->get();
                    // $documents = '';
                    // foreach ($stage_documents as $stage_document) {
                    //     $documents .= ucfirst($stage_document->document_type) . ', ';
                    // }
                    $delete_button = '<button type="button" class="btn btn-danger btn-xs" onclick="deleteApprovalStage(' . $stage->id . ')"><i class="fas fa-trash"></i></button>';
                    $html .= '<li class="list-group-item d-flex justify-content-between align-items-center">' . $stage->project . ' - ' . $stage->department->department_name . ' - ' . ucfirst($stage->document_type === 'payreq' ? 'Payment Request' : $stage->document_type) . $delete_button . '</li>';
                }
                $html .= '</ul>';
                return $html;
            })
            ->addIndexColumn()
            ->addColumn('action', 'approval-stages.action')
            ->rawColumns(['action', 'stages'])
            ->toJson();
    }
}
