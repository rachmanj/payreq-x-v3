<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bapsb extends Model
{
    use HasFactory, SoftDeletes;

    public const VALIDATION_PENDING = 'pending';

    public const VALIDATION_VALIDATED = 'validated';

    protected $guarded = [];

    protected $casts = [
        'bapsb_date' => 'date',
        'validated_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(BapsbLine::class);
    }

    public function dokumen()
    {
        return $this->belongsTo(Dokumen::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isEditable(): bool
    {
        return $this->submitted_at === null;
    }

    public function lineCount(): int
    {
        return $this->count_bg + $this->count_cek + $this->count_loa;
    }
}
