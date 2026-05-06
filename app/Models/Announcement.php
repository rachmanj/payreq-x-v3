<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'start_date',
        'duration_days',
        'status',
        'target_roles',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'duration_days' => 'integer',
        'target_roles' => 'array',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        $today = Carbon::today();

        $query->where('start_date', '<=', $today);

        return match (DB::connection()->getDriverName()) {
            'sqlite' => $query->whereRaw(
                "date(date(start_date), '+' || duration_days || ' days') >= date(?)",
                [$today->toDateString()]
            ),
            default => $query->whereRaw(
                'DATE_ADD(start_date, INTERVAL duration_days DAY) >= ?',
                [$today]
            ),
        };
    }

    public function scopeActiveAndCurrent($query)
    {
        return $query->active()->current();
    }

    public function scopeForUserRoles($query, $userRoles)
    {
        return $query->where(function ($q) use ($userRoles) {
            foreach ($userRoles as $role) {
                $q->orWhereJsonContains('target_roles', $role);
            }
        });
    }

    public function scopeVisibleToUser($query, $user)
    {
        $userRoles = $user->roles->pluck('name')->toArray();

        return $query->activeAndCurrent()->forUserRoles($userRoles);
    }

    // Accessors
    public function getEndDateAttribute()
    {
        return $this->start_date->addDays($this->duration_days);
    }

    public function getIsCurrentAttribute()
    {
        $today = Carbon::today();

        return $this->start_date <= $today && $this->end_date >= $today;
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < Carbon::today();
    }

    public function getTargetRolesStringAttribute()
    {
        return is_array($this->target_roles) ? implode(', ', $this->target_roles) : '';
    }

    // Helper Methods
    public function isVisibleToUser($user)
    {
        if (! $this->is_current || $this->status !== 'active') {
            return false;
        }

        $userRoles = $user->roles->pluck('name')->toArray();

        return ! empty(array_intersect($userRoles, $this->target_roles ?? []));
    }
}
