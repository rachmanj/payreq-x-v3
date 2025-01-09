<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\UserController;
use App\Models\Delivery;
use App\Models\VerificationJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    public function index()
    {
        $page = request()->query('page', 'dashboard');

        $views = [
            'dashboard' => 'accounting.deliveries.dashboard',
            'create' => 'accounting.deliveries.create',
            'list' => 'accounting.deliveries.list',
            'receive' => 'accounting.deliveries.receive',
        ];

        if ($page == 'create') {
            $verificationJournals = $this->getVJReadyToSend();
            $origin = auth()->user()->project;
            $destination = '000H';

            return view($views[$page], compact('verificationJournals', 'origin', 'destination'));
        } elseif ($page == 'dashboard') {
            $data = $this->generate_dashboard_data();

            return view($views[$page], compact('data'));
        }

        return view($views[$page]);
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'document_date' => 'required|date', // Changed from delivery_date to document_date
            'origin' => 'required|string',
            'destination' => 'required|string',
            'recipient_name' => 'required|string',
            'verification_journals' => 'array', // Optional: if you want to allow no selection
        ]);

        $delivery_number = app(DocumentNumberController::class)->generate_document_number('delivery', auth()->user()->project);

        // Create a new delivery
        $delivery = new Delivery();
        $delivery->delivery_number = $delivery_number;
        $delivery->document_date = $request->document_date; // Changed from delivery_date to document_date
        $delivery->origin = $request->origin;
        $delivery->destination = $request->destination;
        $delivery->recipient_name = $request->recipient_name;
        $delivery->status = 'pending'; // Changed from delivery_status to status
        $delivery->remarks = $request->remarks; // Added remarks
        $delivery->created_by = auth()->id(); // Assuming the user is authenticated
        $delivery->save();

        // Attach selected verification journals to the delivery
        if ($request->has('verification_journals')) {
            foreach ($request->verification_journals as $journalId) {
                // Update the verification journal to associate it with the delivery
                DB::table('verification_journals')
                    ->where('id', $journalId)
                    ->update(['delivery_id' => $delivery->id]);
            }
        }

        return redirect()->route('accounting.deliveries.index', ['page' => 'list'])->with('success', 'Delivery created successfully!');
    }

    public function getVJReadyToSend()
    {
        $journals = VerificationJournal::where('project', auth()->user()->project)
            ->whereNotNull('sap_journal_no')
            ->whereNull('delivery_id')
            ->with(['realizations' => function ($query) {
                $query->select('id', 'nomor', 'verification_journal_id')
                    ->addSelect(DB::raw("DATE_FORMAT(created_at, '%d-%b-%Y') as realization_date")); // Select necessary fields with formatted date
            }])
            ->get();

        return $journals;
    }

    public function data()
    {
        $deliveries = Delivery::where('origin', auth()->user()->project)
            ->where('id', '!=', 1) // Exclude the record with id = 1
            ->orderBy('created_at', 'desc')
            ->latest()
            ->get();

        return datatables()->of($deliveries)
            ->addColumn('vj_no', function ($delivery) {
                return '<small>' . $delivery->verificationJournals->pluck('sap_journal_no')->implode('</small>, <small>') . '</small>';
            })
            ->addColumn('document_date', function ($delivery) {
                return '<small>' . \Carbon\Carbon::parse($delivery->document_date)->format('d M Y') . '</small>';
            })
            ->addColumn('destination', function ($delivery) {
                return '<small>' . $delivery->destination . '</small><br><small>' . $delivery->recipient_name . '</small>';
            })
            ->addColumn('status', function ($delivery) {
                $badgeClass = '';
                $statusText = '';

                switch ($delivery->status) {
                    case 'pending':
                        $badgeClass = 'badge-danger';
                        $statusText = 'Pending';
                        break;
                    case 'sent':
                        $badgeClass = 'badge-warning';
                        $statusText = 'Sent on ' . \Carbon\Carbon::parse($delivery->sent_date)->format('d M Y');
                        break;
                    case 'received':
                        $badgeClass = 'badge-success';
                        $statusText = 'Received on ' . \Carbon\Carbon::parse($delivery->received_date)->format('d M Y');
                        break;
                    default:
                        $badgeClass = 'badge-secondary';
                        $statusText = 'Unknown';
                }

                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->addColumn('realizations', function ($delivery) {
                return '<small>' . $delivery->verificationJournals->flatMap(function ($journal) {
                    return $journal->realizations->pluck('nomor')->map(function ($realization) {
                        return $realization;
                    });
                })->implode('</small>, <small>') . '</small>';
            })
            ->addColumn('delivery_number', function ($delivery) {
                return '<small>' . $delivery->delivery_number . '</small>';
            })
            ->addColumn('action', 'accounting.deliveries.action')
            ->addIndexColumn()
            ->rawColumns(['status', 'action', 'vj_no', 'document_date', 'delivery_number', 'destination'])
            ->toJson();
    }

    public function edit($id)
    {
        $delivery = Delivery::with('verificationJournals')->findOrFail($id);

        // Get both existing attached journals and new ones ready to send
        $verificationJournals = VerificationJournal::where(function ($query) use ($delivery) {
            $query->whereNotNull('sap_journal_no')
                ->where('project', auth()->user()->project) // Filter by user project
                ->where(function ($q) use ($delivery) {
                    $q->whereNull('delivery_id')
                        ->orWhere('delivery_id', $delivery->id); // Include journals already attached to this delivery
                });
        })
            ->with(['realizations' => function ($query) {
                $query->select('id', 'nomor', 'verification_journal_id')
                    ->addSelect(DB::raw("DATE_FORMAT(created_at, '%d-%b-%Y') as realization_date"));
            }])
            ->get();

        // Get currently attached verification journal IDs
        $selectedJournalIds = $delivery->verificationJournals->pluck('id')->toArray();

        return view('accounting.deliveries.edit', compact('delivery', 'verificationJournals', 'selectedJournalIds'));
    }

    public function update(Request $request, $id)
    {
        // Validate the incoming request
        $request->validate([
            'recipient_name' => 'required|string',
            'verification_journals' => 'array',
        ]);

        DB::beginTransaction();
        try {
            // Update the delivery
            $delivery = Delivery::findOrFail($id);
            $delivery->recipient_name = $request->recipient_name;
            $delivery->remarks = $request->remarks; // Assuming remarks is passed in the request
            $delivery->save();

            // Reset delivery_id to null for all previously attached journals
            DB::table('verification_journals')
                ->where('delivery_id', $delivery->id)
                ->update(['delivery_id' => null]);

            // Attach selected verification journals to the delivery
            if ($request->has('verification_journals')) {
                DB::table('verification_journals')
                    ->whereIn('id', $request->verification_journals)
                    ->update(['delivery_id' => $delivery->id]);
            }

            DB::commit();

            return redirect()->route('accounting.deliveries.index', ['page' => 'list'])
                ->with('success', 'Delivery updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error updating delivery: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $delivery = Delivery::with(['verificationJournals.realizations'])
            ->findOrFail($id);

        return view('accounting.deliveries.print', compact('delivery'));
    }

    public function send($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = 'sent'; // Adjusted to match the new table structure
        $delivery->sent_date = now();
        $delivery->save();

        return redirect()->route('accounting.deliveries.index', ['page' => 'list'])->with('success', 'Delivery sent successfully!');
    }

    public function destroy($id)
    {
        $delivery = Delivery::findOrFail($id);

        // Reset delivery_id to null for all attached verification journals
        DB::table('verification_journals')
            ->where('delivery_id', $delivery->id)
            ->update(['delivery_id' => null]);

        $delivery->delete();
        return redirect()->route('accounting.deliveries.index', ['page' => 'list'])->with('success', 'Delivery deleted successfully!');
    }

    public function show($id)
    {
        $delivery = Delivery::with(['verificationJournals.realizations'])
            ->findOrFail($id);

        return view('accounting.deliveries.show', compact('delivery'));
    }

    public function receive_data()
    {
        $deliveries = Delivery::where('destination', auth()->user()->project)
            ->where('status', '!=', 'pending')
            ->where('id', '!=', 1) // Exclude delivery with id = 1
            ->orderBy('sent_date', 'desc')
            ->limit(100) // Limit to 100 records
            ->get();

        return datatables()->of($deliveries)
            ->addColumn('delivery_number', function ($delivery) {
                return '<small>' . $delivery->delivery_number . '</small><br>' .
                    '<small>Verification Journals Count: ' . $delivery->verificationJournals()->count() . '</small>';
            })
            ->addColumn('sent_date', function ($delivery) {
                return '<small>Sent: ' . \Carbon\Carbon::parse($delivery->sent_date)->format('d M Y') . '</small><br>' .
                    '<small>Received: ' . \Carbon\Carbon::parse($delivery->received_date)->format('d M Y') . '</small>';
            })
            ->addColumn('origin', function ($delivery) {
                return '<small>' . $delivery->origin . '</small>';
            })
            ->addColumn('sender_name', function ($delivery) {
                return '<small>Sender: ' . $delivery->createdBy->name . '</small><br><small>Receiver: ' . ($delivery->receivedBy ? $delivery->receivedBy->name : 'N/A') . '</small>'; // Assuming 'createdBy' and 'receivedBy' relationships exist
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.deliveries.receive_action')
            ->rawColumns(['action', 'sent_date', 'origin', 'delivery_number', 'received_date', 'sender_name'])
            ->toJson();
    }

    public function updateReceiveInfo(Request $request, $id)
    {
        // Validate the incoming request
        $request->validate([
            'receiveDate' => 'required|date',
        ]);

        // Find the delivery record
        $delivery = Delivery::findOrFail($id);

        // Update the delivery status and other fields
        $delivery->status = 'received'; // Set status to received
        $delivery->received_date = $request->receiveDate; // Set the current date and time
        $delivery->feedback = $request->feedback; // Update feedback
        $delivery->received_by = auth()->user()->id; // Set the receiver user ID
        $delivery->save(); // Save the changes

        return redirect()->route('accounting.deliveries.index', ['page' => 'receive'])->with('success', 'Delivery received successfully!');
    }

    public function generate_dashboard_data()
    {
        // Define all possible projects (excluding 000H since it's the destination)
        $allProjects = ['001H', '017C', '021C', '022C', '023C'];

        // Get user roles using UserController
        $userRoles = app(UserController::class)->getUserRoles();
        $projects = array_intersect(['admin', 'superadmin', 'cashier'], $userRoles)
            ? $allProjects  // All projects except 000H
            : array_diff(explode(',', auth()->user()->project), ['000H']);  // User's projects except 000H

        // Get all years from received_date
        $years = Delivery::whereNotNull('received_date')
            ->where('destination', '000H')
            ->whereIn('origin', $projects)
            ->selectRaw('YEAR(received_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $monthNames = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
        ];

        $result = [];

        foreach ($years as $year) {
            $yearData = [];

            // Initialize all projects with empty data for this year
            foreach ($projects as $project) {
                $months_data = [];
                foreach ($months as $month) {
                    $months_data[$month] = [
                        'month' => $month,
                        'month_name' => $monthNames[$month],
                        'count' => 0,
                        'status' => 'missing',
                        'deliveries' => [],
                        'received_dates' => []
                    ];
                }

                $yearData[$project] = [
                    'project' => $project,
                    'months' => $months_data,
                    'total_deliveries' => 0
                ];
            }

            // Fill in actual delivery data for this year
            foreach ($projects as $project) {
                $deliveries = Delivery::where('origin', $project)
                    ->where('destination', '000H')
                    ->whereNotNull('received_date')
                    ->whereYear('received_date', $year)
                    ->whereIn(DB::raw('LPAD(MONTH(received_date), 2, "0")'), $months)
                    ->orderBy(DB::raw('MONTH(received_date)'), 'asc')
                    ->get()
                    ->groupBy(function ($item) {
                        return Carbon::parse($item->received_date)->format('m');
                    });

                foreach ($months as $month) {
                    $delivery = $deliveries->get($month);
                    if ($delivery) {
                        $yearData[$project]['months'][$month] = [
                            'month' => $month,
                            'month_name' => $monthNames[$month],
                            'count' => $delivery->count(),
                            'status' => 'completed',
                            'deliveries' => $delivery->map(function ($d) {
                                return [
                                    'id' => $d->id,
                                    'nomor' => $d->delivery_number,
                                    'date' => Carbon::parse($d->received_date)->format('d M Y'),
                                    'received_date' => Carbon::parse($d->received_date)->format('Y-m-d'),
                                    'recipient' => $d->recipient_name,
                                    'journals_count' => $d->verificationJournals->count()
                                ];
                            }),
                            'received_dates' => $delivery->map(function ($d) {
                                return Carbon::parse($d->received_date)->format('d');
                            })->sort()->values()->toArray()
                        ];
                    }
                }

                $yearData[$project]['total_deliveries'] = $deliveries->flatten()->count();
            }

            $result[$year] = [
                'year' => $year,
                'projects' => $yearData
            ];
        }

        return $result;
    }
}
