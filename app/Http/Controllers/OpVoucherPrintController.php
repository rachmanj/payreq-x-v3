<?php

namespace App\Http\Controllers;

use App\Models\BpjsApInvoice;
use App\Models\SapSubmissionLog;
use App\Services\OpVoucherService;
use Illuminate\View\View;

class OpVoucherPrintController extends Controller
{
    public function printBpjs(BpjsApInvoice $bpjsApInvoice, OpVoucherService $opVoucherService): View
    {
        $log = SapSubmissionLog::query()
            ->where('bpjs_ap_invoice_id', $bpjsApInvoice->id)
            ->where('document_type', SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT)
            ->where('status', 'success')
            ->orderByDesc('id')
            ->first();

        if ($log === null) {
            abort(404, 'Outgoing Payment SAP belum tersedia untuk invoice BPJS ini. Lakukan pembayaran SAP terlebih dahulu.');
        }

        return $this->renderVoucher($opVoucherService, $log);
    }

    public function printDds(int $ddsInvoiceId, OpVoucherService $opVoucherService): View
    {
        $log = SapSubmissionLog::query()
            ->where('dds_invoice_id', $ddsInvoiceId)
            ->where('document_type', SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT)
            ->where('status', 'success')
            ->orderByDesc('id')
            ->first();

        if ($log === null) {
            abort(404, 'Outgoing Payment SAP belum tersedia untuk invoice DDS ini. Lakukan pembayaran SAP terlebih dahulu.');
        }

        return $this->renderVoucher($opVoucherService, $log);
    }

    protected function renderVoucher(OpVoucherService $opVoucherService, SapSubmissionLog $log): View
    {
        try {
            $voucher = $opVoucherService->build($log);
        } catch (\Throwable $exception) {
            return view('prints.op-voucher-error', [
                'message' => $exception->getMessage(),
            ]);
        }

        if ($voucher['header'] === [] || $voucher['lines'] === []) {
            return view('prints.op-voucher-error', [
                'message' => 'Data voucher OP tidak lengkap: header atau baris akun kosong.',
            ]);
        }

        return view('prints.op-voucher', [
            'voucher' => $voucher,
        ]);
    }
}
