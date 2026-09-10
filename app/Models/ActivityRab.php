<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRab extends Model
{
    protected $table = 'activity_rab';

    protected $guarded = [];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function anggaran(): BelongsTo
    {
        return $this->belongsTo(Anggaran::class);
    }
}
