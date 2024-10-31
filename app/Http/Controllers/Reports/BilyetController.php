<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Bilyet;
use App\Models\Giro;
use Illuminate\Http\Request;

class BilyetController extends Controller
{
    public function index()
    {
        $data = $this->dashboardData();

        return view('reports.bilyet.index', compact('data'));
    }

    public function dashboardData()
    {
        return [
            'onhands' => $this->getBilyetData('onhand'),
            'released' => $this->getBilyetData('release'),
            'void' => $this->getBilyetData('void'),
            'due_this_month' => $this->getBilyetDataReleaseThisMonth(),
        ];
    }

    private function getBilyetData($status)
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $giros = $this->getGirosBasedOnUserRoles($userRoles);

        $bilyetTypes = ['cek', 'bg', 'loa', 'debit'];
        $result = [];

        foreach ($giros as $giro) {
            $giroData = $this->initializeGiroData($giro);

            foreach ($bilyetTypes as $type) {
                $giroData = $this->updateGiroData($giroData, $status, $type, $giro->id);
            }

            if ($giroData['total'] > 0) {
                $result[] = $giroData;
            }
        }

        return $result;
    }

    private function getGirosBasedOnUserRoles($userRoles)
    {
        if (array_intersect(['superadmin', 'admin', 'cashier'], $userRoles)) {
            return Giro::orderBy('id', 'asc')->get();
        }

        $user = auth()->user();
        if ($user) {
            return Giro::orderBy('id', 'asc')->where('project', $user->project)->get();
        }

        return collect(); // Return an empty collection if the user is not authenticated
    }

    private function initializeGiroData($giro)
    {
        return [
            'giro_id' => $giro->id,
            'acc_no' => $giro->acc_no,
            'acc_name' => $giro->acc_name,
            'total' => 0,
            'amount' => 0,
        ];
    }

    private function updateGiroData($giroData, $status, $type, $giroId)
    {
        $count = $this->getBilyetCountByType($status, $type, $giroId);
        $amount = $this->getBilyetAmountByType($status, $type, $giroId);
        $giroData[$type] = $count;
        $giroData['total'] += $count;
        $giroData['amount'] += $amount;

        return $giroData;
    }

    private function getBilyetCountByType($status, $type, $giroId)
    {
        return Bilyet::where('status', $status)
            ->where('bilyets.type', $type)
            ->where('bilyets.giro_id', $giroId)
            ->join('giros', 'bilyets.giro_id', '=', 'giros.id')
            ->selectRaw('COUNT(bilyets.id) as count')
            ->groupBy('bilyets.giro_id')
            ->first()
            ->count ?? 0;
    }

    private function getBilyetAmountByType($status, $type, $giroId)
    {
        return Bilyet::where('status', $status)
            ->where('bilyets.type', $type)
            ->where('bilyets.giro_id', $giroId)
            ->join('giros', 'bilyets.giro_id', '=', 'giros.id')
            ->selectRaw('SUM(bilyets.amount) as total_amount')
            ->groupBy('bilyets.giro_id')
            ->first()
            ->total_amount ?? 0;
    }

    private function getBilyetDataReleaseThisMonth()
    {
        $giros = Giro::orderBy('id', 'asc')->get();
        $bilyetTypes = ['cek', 'bg', 'loa', 'debit'];
        $result = [];

        foreach ($giros as $giro) {
            $giroData = $this->initializeGiroData($giro);

            foreach ($bilyetTypes as $type) {
                $giroData = $this->updateGiroDataReleaseThisMonth($giroData, $type, $giro->id);
            }

            if ($giroData['total'] > 0) {
                $result[] = $giroData;
            }
        }

        return $result;
    }

    private function updateGiroDataReleaseThisMonth($giroData, $type, $giroId)
    {
        $count = $this->getBilyetCountByTypeReleaseThisMonth($type, $giroId);
        $amount = $this->getBilyetAmountByTypeReleaseThisMonth($type, $giroId);
        $giroData[$type] = $count;
        $giroData['total'] += $count;
        $giroData['amount'] += $amount;

        return $giroData;
    }

    private function getBilyetCountByTypeReleaseThisMonth($type, $giroId)
    {
        return Bilyet::where('status', 'release')
            ->where('bilyets.type', $type)
            ->where('bilyets.giro_id', $giroId)
            ->where(function ($query) {
                $query->whereMonth('bilyet_date', '<=', date('m'))
                    ->whereYear('bilyet_date', '<=', date('Y'));
            })
            ->join('giros', 'bilyets.giro_id', '=', 'giros.id')
            ->selectRaw('COUNT(bilyets.id) as count')
            ->groupBy('bilyets.giro_id')
            ->first()
            ->count ?? 0;
    }

    private function getBilyetAmountByTypeReleaseThisMonth($type, $giroId)
    {
        return Bilyet::where('status', 'release')
            ->where('bilyets.type', $type)
            ->where('bilyets.giro_id', $giroId)
            ->where(function ($query) {
                $query->whereMonth('bilyet_date', '<=', date('m'))
                    ->whereYear('bilyet_date', '<=', date('Y'));
            })
            ->join('giros', 'bilyets.giro_id', '=', 'giros.id')
            ->selectRaw('SUM(bilyets.amount) as total_amount')
            ->groupBy('bilyets.giro_id')
            ->first()
            ->total_amount ?? 0;
    }
}
