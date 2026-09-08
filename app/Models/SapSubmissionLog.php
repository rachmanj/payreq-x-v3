<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapSubmissionLog extends Model
{
    use HasFactory;

    public const DOCUMENT_TYPE_INVOICE_PAYMENT = 'invoice_payment';

    public const DOCUMENT_TYPE_BPJS_AP_INVOICE = 'bpjs_ap_invoice';

    public const DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT = 'bpjs_ap_invoice_payment';

    public const DOCUMENT_TYPE_AP_INVOICE_INSTALLMENT = 'ap_invoice_installment';

    public const DOCUMENT_TYPE_AP_OUTGOING_INSTALLMENT = 'ap_outgoing_installment';

    protected $fillable = [
        'verification_journal_id',
        'journal_entry_id',
        'faktur_id',
        'utility_ap_invoice_id',
        'bpjs_ap_invoice_id',
        'dds_invoice_id',
        'dds_invoice_number',
        'document_type',
        'user_id',
        'submitted_by',
        'status',
        'action',
        'error_message',
        'sap_error',
        'sap_response',
        'sap_journal_number',
        'sap_doc_num',
        'sap_doc_entry',
        'amount',
        'attempt_number',
    ];

    protected $casts = [
        'sap_response' => 'array',
    ];

    public function verificationJournal()
    {
        return $this->belongsTo(VerificationJournal::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function faktur()
    {
        return $this->belongsTo(Faktur::class);
    }

    public function utilityApInvoice()
    {
        return $this->belongsTo(UtilityApInvoice::class);
    }

    public function bpjsApInvoice()
    {
        return $this->belongsTo(BpjsApInvoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
