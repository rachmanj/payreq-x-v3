<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ExchangeRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'currency_from',
        'currency_to',
        'exchange_rate',
        'effective_date',
        'created_by',
        'updated_by',
        'kmk_number',
        'kmk_effective_from',
        'kmk_effective_to',
        'source',
        'change_from_previous',
        'scraped_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'effective_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'kmk_effective_from' => 'date',
        'kmk_effective_to' => 'date',
        'change_from_previous' => 'decimal:6',
        'scraped_at' => 'datetime',
    ];

    /**
     * Get the 'from' currency.
     */
    public function currencyFromRelation(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_from', 'currency_code');
    }

    /**
     * Get the 'to' currency.
     */
    public function currencyToRelation(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_to', 'currency_code');
    }

    /**
     * Get the user who created this exchange rate.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this exchange rate.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to filter by currency pair.
     */
    public function scopeByCurrencyPair($query, $currencyFrom, $currencyTo)
    {
        return $query->where('currency_from', $currencyFrom)
            ->where('currency_to', $currencyTo);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('effective_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get rates for a specific date.
     */
    public function scopeByDate($query, $date)
    {
        return $query->where('effective_date', $date);
    }

    /**
     * Get formatted exchange rate display.
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->exchange_rate, 6);
    }

    /**
     * Get currency pair display (FROM/TO).
     */
    public function getCurrencyPairAttribute(): string
    {
        return $this->currency_from . '/' . $this->currency_to;
    }

    /**
     * Validation rules for exchange rate.
     */
    public static function validationRules(): array
    {
        return [
            'currency_from' => 'required|string|size:3|exists:currencies,currency_code',
            'currency_to' => 'required|string|size:3|exists:currencies,currency_code|different:currency_from',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'effective_date' => 'required|date',
            'created_by' => 'required|exists:users,id',
        ];
    }

    /**
     * Boot method to add model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Add validation before saving
        static::saving(function ($model) {
            if ($model->currency_from === $model->currency_to) {
                throw new \InvalidArgumentException('Currency From and Currency To must be different.');
            }

            if ($model->exchange_rate <= 0) {
                throw new \InvalidArgumentException('Exchange Rate must be greater than 0.');
            }
        });
    }
}
