<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Creditor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sap_business_partner_id',
    ];

    /**
     * Relationship to SAP Business Partner
     */
    public function sapBusinessPartner()
    {
        return $this->belongsTo(SapBusinessPartner::class);
    }

    /**
     * Get SAP CardCode via relationship
     */
    public function getSapCodeAttribute(): ?string
    {
        return $this->sapBusinessPartner?->code;
    }

    /**
     * Check if creditor is linked to SAP Business Partner
     */
    public function hasSapPartner(): bool
    {
        return $this->sap_business_partner_id !== null;
    }

    /**
     * Scope to find creditors with SAP link
     */
    public function scopeWithSapPartner($query)
    {
        return $query->whereNotNull('sap_business_partner_id');
    }

    /**
     * Scope to find creditors without SAP link
     */
    public function scopeWithoutSapPartner($query)
    {
        return $query->whereNull('sap_business_partner_id');
    }
}
