<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapBusinessPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'active',
        'phone',
        'email',
        'address',
        'vat_liable',
        'federal_tax_id',
        'credit_limit',
        'balance',
        'metadata',
        'last_synced_at',
        'previous_name',
        'previous_active',
        'name_changed_at',
        'status_changed_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'vat_liable' => 'boolean',
        'previous_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'name_changed_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    const TYPE_CUSTOMER = 'cCustomer';
    const TYPE_SUPPLIER = 'cSupplier';
    const TYPE_LEAD = 'cLead';

    public function isCustomer(): bool
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    public function isSupplier(): bool
    {
        return $this->type === self::TYPE_SUPPLIER;
    }

    public function isLead(): bool
    {
        return $this->type === self::TYPE_LEAD;
    }

    /**
     * Relationship to Creditors
     */
    public function creditors()
    {
        return $this->hasMany(Creditor::class);
    }

    /**
     * Scope for suppliers/vendors only
     * Handles both 'S' and 'cSupplier' formats
     */
    public function scopeSuppliers($query)
    {
        return $query->where(function ($q) {
            $q->where('type', 'S')
              ->orWhere('type', self::TYPE_SUPPLIER);
        });
    }

    /**
     * Scope for active partners only
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
