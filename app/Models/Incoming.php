<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incoming extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function realization()
    {
        return $this->belongsTo(Realization::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id', 'id');
    }
}
