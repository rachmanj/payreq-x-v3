<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RealizationDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expense_date' => 'date',
    ];

    /**
     * DATE columns must serialize as calendar Y-m-d for JSON/API: ISO8601 UTC shifts the calendar day for JS substring(0, 10).
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        if (! array_key_exists('expense_date', $attributes)) {
            return $attributes;
        }

        $attributes['expense_date'] = $this->calendarExpenseDateForArray();

        return $attributes;
    }

    protected function calendarExpenseDateForArray(): ?string
    {
        $raw = $this->getRawOriginal('expense_date');
        if ($raw !== null && $raw !== '') {
            return substr((string) $raw, 0, 10);
        }

        $value = $this->expense_date;
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->copy()->timezone(config('app.timezone'))->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }

    public function realization()
    {
        return $this->belongsTo(Realization::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
