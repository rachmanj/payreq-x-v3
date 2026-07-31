<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_hidden', false);
    }

    public static function forVjDetailSelection(string $debitCredit, ?string $project = null): Collection
    {
        $query = static::query()
            ->selectable()
            ->orderBy('account_number');

        if ($debitCredit === 'credit' && $project) {
            $query->whereIn('type', ['cash', 'bank'])
                ->where('project', $project);
        }

        return $query->get();
    }
}
