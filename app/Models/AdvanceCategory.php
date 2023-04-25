<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceCategory extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payreqs()
    {
        return $this->hasMany(Payreq::class, 'advance_category_id', 'id');
    }
}
