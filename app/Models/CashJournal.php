<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashJournal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function outgoings()
    {
        return $this->hasMany(Outgoing::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
