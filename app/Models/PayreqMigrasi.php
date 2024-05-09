<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayreqMigrasi extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payreq()
    {
        return $this->belongsTo(Payreq::class);
    }
}
