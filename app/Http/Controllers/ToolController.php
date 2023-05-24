<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Rab;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function getProjects()
    {
        $url = env('URL_PROJECTS');
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $projects = json_decode($response->getBody()->getContents(), true)['data'];

        return $projects;
    }

    public function progress($rab_id)
    {
        $rab = Rab::find($rab_id);
        $payreqs = $rab->payreqs;
        $total_advance = $payreqs->whereNotNull('outgoing_date')->whereNull('realization_date')->sum('payreq_idr');
        $total_realization = $payreqs->whereNotNull('realization_date')->sum('realization_amount');
        $total_release = $total_advance + $total_realization;
        $progress = ($total_release / $rab->budget) * 100;

        return $progress;
    }

    public function statusColor($progress)
    {
        if ($progress == 100) {
            return 'bg-success';
        } elseif ($progress > 0 && $progress < 100) {
            return 'bg-warning';
        } else {
            return 'bg-danger';
        }
    }

    public function terbilang($value)
    {
        $value = number_format($value, 0, ',', '.');
        $value = str_replace(',', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(' ', '', $value);
        $value = (int) $value;
        $huruf = [
            '',
            'Satu',
            'Dua',
            'Tiga',
            'Empat',
            'Lima',
            'Enam',
            'Tujuh',
            'Delapan',
            'Sembilan',
            'Sepuluh',
            'Sebelas',
        ];
        if ($value < 12) {
            return ' ' . $huruf[$value];
        } elseif ($value < 20) {
            return $this->terbilang($value - 10) . ' Belas';
        } elseif ($value < 100) {
            return $this->terbilang($value / 10) . ' Puluh' . $this->terbilang($value % 10);
        } elseif ($value < 200) {
            return ' Seratus' . $this->terbilang($value - 100);
        } elseif ($value < 1000) {
            return $this->terbilang($value / 100) . ' Ratus' . $this->terbilang($value % 100);
        } elseif ($value < 2000) {
            return ' Seribu' . $this->terbilang($value - 1000);
        } elseif ($value < 1000000) {
            return $this->terbilang($value / 1000) . ' Ribu' . $this->terbilang($value % 1000);
        } elseif ($value < 1000000000) {
            return $this->terbilang($value / 1000000) . ' Juta' . $this->terbilang($value % 1000000);
        } elseif ($value < 1000000000000) {
            return $this->terbilang($value / 1000000000) . ' Milyar' . $this->terbilang(fmod($value, 1000000000));
        } elseif ($value < 1000000000000000) {
            return $this->terbilang($value) / 1000000000000 . ' Trilyun' . $this->terbilang(fmod($value, 1000000000000));
        }

        return 'Angka terlalu besar';
    }
}
