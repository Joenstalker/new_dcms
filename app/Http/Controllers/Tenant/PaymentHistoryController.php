<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantPaymentHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentHistoryController extends Controller
{
    public function downloadReceipt(int $id)
    {
        $history = $this->resolveTenantHistory($id);
        $paidAt = $history->paid_at ?? $history->created_at;

        $signature = now()->format('YmdHis').Str::upper(Str::random(3));

        $pdf = Pdf::loadView('billing.receipt-pdf', [
            'history' => $history,
            'tenant' => tenant(),
            'paidAt' => $paidAt,
            'signature' => $signature,
            'displayTransactionId' => $history->stripe_charge_id
                ?: $history->stripe_payment_intent_id
                ?: $history->stripe_invoice_id
                ?: $history->transaction_code,
        ]);

        return $pdf->download('receipt-'.$history->transaction_code.'.pdf');
    }

    public function downloadInvoice(int $id)
    {
        $history = $this->resolveTenantHistory($id);

        if ($history->status !== 'success') {
            abort(403, 'Invoice download is available only for successful transactions.');
        }

        $invoiceDate = $history->paid_at ?? $history->created_at;
        $dueDate = Carbon::parse($invoiceDate)->copy()->addDays(7);
        $invoiceNumber = 'INV-'.now()->format('Y').'-'.$history->transaction_code;

        $pdf = Pdf::loadView('billing.invoice-pdf', [
            'history' => $history,
            'tenant' => tenant(),
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate,
            'dueDate' => $dueDate,
            'quantity' => 1,
            'rate' => (float) $history->amount,
            'subtotal' => (float) $history->amount,
            'tax' => 0.00,
            'total' => (float) $history->amount,
            'appliedTransaction' => $history->stripe_charge_id
                ?: $history->stripe_payment_intent_id
                ?: $history->transaction_code,
        ]);

        return $pdf->download('invoice-'.$history->transaction_code.'.pdf');
    }

    private function resolveTenantHistory(int $id): TenantPaymentHistory
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(404);
        }

        return TenantPaymentHistory::query()
            ->where('id', $id)
            ->firstOrFail();
    }
}
