<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payreq extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // relationship with department
    public function department()
    {
        return $this->hasOneThrough(Department::class, User::class, 'id', 'id', 'user_id', 'department_id');
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id', 'id')->withDefault([
            'rab_no' => 'n/a',
        ]);
    }

    public function splits()
    {
        return $this->hasMany(Split::class, 'payreq_id', 'id');
    }

    public function advance_category()
    {
        return $this->belongsTo(AdvanceCategory::class, 'adv_category_id', 'id')->withDefault([
            'adv_category_code' => 'n/a',
        ]);
    }
}
