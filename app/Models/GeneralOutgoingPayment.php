<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneralOutgoingPayment extends Model
{
    use HasFactory;

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'giro_id',
        'bilyet_id',
        'posting_date',
        'doc_date',
        'amount',
        'remarks',
        'project',
        'profit_center',
        'akun_tujuan_utama',
        'sap_doc_num',
        'sap_doc_entry',
        'status',
        'created_by',
        'submitted_at',
        'sap_error_message',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'doc_date' => 'date',
        'amount' => 'integer',
        'sap_doc_entry' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function giro(): BelongsTo
    {
        return $this->belongsTo(Giro::class);
    }

    public function bilyet(): BelongsTo
    {
        return $this->belongsTo(Bilyet::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(GeneralOutgoingPaymentAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
