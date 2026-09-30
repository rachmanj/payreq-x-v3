<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoretaxInputVat extends Model
{
    use HasFactory;

    protected $table = 'coretax_input_vat';

    protected $guarded = [];

    protected $casts = [
        'faktur_date' => 'date',
        'valid_coretax' => 'boolean',
        'dilaporkan' => 'boolean',
        'nilai_bruto' => 'decimal:2',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
    ];

    public function matchedFaktur(): BelongsTo
    {
        return $this->belongsTo(Faktur::class, 'matched_faktur_id');
    }
}
