<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BpjsApInvoice extends Model
{
    use HasFactory;

    public const JENIS_KESEHATAN = 'kesehatan';

    public const JENIS_KETENAGAKERJAAN = 'ketenagakerjaan';

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const CARD_CODES = [
        self::JENIS_KESEHATAN => 'VBPKEIDR01',
        self::JENIS_KETENAGAKERJAAN => 'VBPTKIDR01',
    ];

    public const ACCOUNT_CODES = [
        self::JENIS_KESEHATAN => '61201004',
        self::JENIS_KETENAGAKERJAAN => '21601001',
    ];

    public const EXPENSE_ACCOUNT_CODES = [
        self::JENIS_KESEHATAN => '61201004',
        self::JENIS_KETENAGAKERJAAN => '61201003',
    ];

    public const ACCRUAL_ACCOUNT_CODE = '21601001';

    public const JE_STATUS_PENDING = 'pending';

    public const JE_STATUS_SUCCESS = 'success';

    public const JE_STATUS_FAILED = 'failed';

    public const JE_STATUS_SKIPPED = 'skipped';

    public const JE_STATUS_REVERSED = 'reversed';

    public const JENIS_LABELS = [
        self::JENIS_KESEHATAN => 'BPJS Kesehatan',
        self::JENIS_KETENAGAKERJAAN => 'BPJS Ketenagakerjaan',
    ];

    public const SUPPLIER_NAMES = [
        self::JENIS_KESEHATAN => 'BPJS KESEHATAN',
        self::JENIS_KETENAGAKERJAAN => 'BPJS KETENAGAKERJAAN',
    ];

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'doc_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'submitted_at' => 'datetime',
        'auto_je' => 'boolean',
        'je_posting_date' => 'date',
        'je_submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function jeSubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'je_submitted_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public static function defaultJePostingDate(string $periode): string
    {
        return Carbon::createFromFormat('Y-m', $periode)
            ->subMonth()
            ->endOfMonth()
            ->format('Y-m-d');
    }

    public function submissionLogs(): HasMany
    {
        return $this->hasMany(SapSubmissionLog::class, 'bpjs_ap_invoice_id');
    }

    public static function unitLabel(string $unit): string
    {
        return $unit === '000H' ? 'HO' : 'NS '.$unit;
    }

    public function invoiceNumber(): string
    {
        $prefix = $this->jenis === self::JENIS_KESEHATAN ? 'KES' : 'TK';

        return 'BPJS-'.$prefix.'-'.$this->unit.'-'.$this->num_at_card;
    }

    public function supplierSapCode(): string
    {
        return self::CARD_CODES[$this->jenis] ?? '';
    }

    public function remainingAmount(): float
    {
        return max(0.0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function isDuplicateBlocked(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_POSTED,
            self::STATUS_PAID,
        ], true);
    }

    public static function hasActiveDuplicate(string $jenis, string $unit, string $periode, ?int $exceptId = null): bool
    {
        $query = self::query()
            ->where('jenis', $jenis)
            ->where('unit', $unit)
            ->where('periode', $periode)
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_POSTED,
                self::STATUS_PAID,
            ]);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}
