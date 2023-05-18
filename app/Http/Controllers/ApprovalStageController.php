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
        $request->validate([
            'approver_id' => 'required',
            'project' => 'required',
            'departments' => 'required',
        ]);

        $departments = Department::whereIn('id', $request->departments)->get();

        foreach ($departments as $department) {
            ApprovalStage::create([
                'department_id' => $department->id,
                'approver_id' => $request->approver_id,
                'project' => $request->project,
            ]);
        }

        // ApprovalStage::create([
        //     'department_id' => $request->department,
        //     'approver_id' => $request->approver_id,
        //     'project' => $request->project,
        // ]);

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
                $stages = ApprovalStage::where('approver_id', $approver->id)->get();
                $html = '<ul class="list-group">';
                foreach ($stages as $stage) {
                    $delete_button = '<button type="button" class="btn btn-danger btn-xs" onclick="deleteApprovalStage(' . $stage->id . ')"><i class="fas fa-trash"></i></button>';
                    $html .= '<li class="list-group-item d-flex justify-content-between align-items-center">' . $stage->project . ' - ' . $stage->department->department_name . ' ' . $delete_button . '</li>';
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

// script.js
// Path: public\js\script.js
// Compare this snippet from public\js\script.js:
// function deleteApprovalStage(id) {
//     Swal.fire({
//         title: 'Are you sure?',
//         text: "You won't be able to revert this!",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Yes, delete it!'
//     }).then((result) => {
//         if (result.isConfirmed) {
//             $.ajax({
//                 url: '/approval-stages/' + id,
//                 type: 'DELETE',
//                 data: {
//                     _token: $('meta[name="csrf-token"]').attr('content'),
//                 },
//                 success: function () {
//                     Swal.fire(
//                         'Deleted!',
//                         'Your file has been deleted.',
//                         'success'
//                     ).then((result) => {
//                         location.reload();
//                     });
//                 }
//             });
//         }
//     });
// }
