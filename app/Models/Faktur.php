<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faktur extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCustomerNameAttribute()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->first()->name;
    }

    public function getCreatedByNameAttribute()
    {
        $user = $this->belongsTo(User::class, 'created_by')->first();
        return $user ? $user->name : '-';
    }

    public function getResponseByNameAttribute()
    {
        $user = $this->belongsTo(User::class, 'response_by')->first();
        return $user ? $user->name : '-';
    }

    public function getAttachmentAttribute($value)
    {
        return $value ? asset('faktur/' . $value) : null;
    }
}
