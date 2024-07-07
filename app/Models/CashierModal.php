<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierModal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitter')->withDefault([
            'name' => '-'
        ]);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'receiver')->withDefault([
            'name' => '-'
        ]);
    }
}
