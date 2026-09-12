<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayreqTransferDestination extends Model
{
    protected $guarded = [];

    public function payreq(): BelongsTo
    {
        return $this->belongsTo(Payreq::class);
    }

    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(TransferAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
