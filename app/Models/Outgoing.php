<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outgoing extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payreq()
    {
        return $this->belongsTo(Payreq::class, 'payreq_id', 'id')->withDefault([
            'nomor' => 'n/a',
        ]);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id', 'id')->withDefault([
            'name' => 'n/a',
        ]);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function cash_journal()
    {
        return $this->belongsTo(CashJournal::class);
    }
    
    /**
     * Get full description including remarks from payreq document
     * 
     * @return string
     */
    public function getFullDescriptionAttribute()
    {
        $description = $this->description ?? '';
        $payreqRemarks = $this->payreq->remarks ?? '';
        
        if (!empty($payreqRemarks) && !str_contains($description, $payreqRemarks)) {
            return trim($description . ' - ' . $payreqRemarks);
        }
        
        return $description;
    }
}
