<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Anggaran extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payreqs()
    {
        return $this->hasMany(Payreq::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Get active anggarans with caching
     * 
     * @param string $project
     * @param array $userRoles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveAnggarans($project, $userRoles = [])
    {
        $cacheKey = 'anggarans_active_' . $project . '_' . implode('_', $userRoles);
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($project, $userRoles) {
            $query = self::query()
                ->with(['createdBy:id,name'])
                ->select(['id', 'nomor', 'rab_no', 'date', 'description', 'amount', 'balance', 'persen', 
                         'rab_project', 'usage', 'created_by', 'periode_anggaran', 'periode_ofr', 'is_active', 'status'])
                ->where('is_active', 1);
                
            if (!array_intersect(['superadmin', 'admin'], $userRoles)) {
                $query->where('project', $project);
            }
            
            if (array_intersect(['superadmin', 'admin'], $userRoles)) {
                $query->whereIn('status', ['approved']);
            }
            
            return $query->get();
        });
    }
    
    /**
     * Get inactive anggarans with caching
     * 
     * @param string $project
     * @param array $userRoles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getInactiveAnggarans($project, $userRoles = [])
    {
        $cacheKey = 'anggarans_inactive_' . $project . '_' . implode('_', $userRoles);
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($project, $userRoles) {
            $query = self::query()
                ->with(['createdBy:id,name'])
                ->select(['id', 'nomor', 'rab_no', 'date', 'description', 'amount', 'balance', 'persen', 
                         'rab_project', 'usage', 'created_by', 'periode_anggaran', 'periode_ofr', 'is_active', 'status'])
                ->where('is_active', 0);
                
            if (!array_intersect(['superadmin', 'admin'], $userRoles)) {
                $query->where('project', $project);
            }
            
            if (array_intersect(['superadmin', 'admin'], $userRoles)) {
                $query->whereIn('status', ['approved']);
            }
            
            return $query->get();
        });
    }
}
