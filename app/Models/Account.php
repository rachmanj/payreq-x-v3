<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function account_type()
    {
        return $this->belongsTo(AccountType::class, 'type_id');
    }
}
