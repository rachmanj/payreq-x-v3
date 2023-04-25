<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    public function payreqs()
    {
        return $this->hasManyThrough(Payreq::class, User::class, 'department_id', 'user_id', 'id', 'id');
    }
}
