<?php
// app/Http/Controllers/Purchasing/PurchasePaymentController.php
namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchasePaymentRequest;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\JournalService;
use App\Services\PurchasePaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasePaymentController extends Controller
{
    /** Tambah pembayaran + jurnal terpisah */
    public function store(
        StorePurchasePaymentRequest $req,
        PurchaseInvoice $invoice,
        JournalService $journal,
        PurchasePaymentService $pps
    ) {
        $data = $req->validated();
        $amount = round((float) $data['amount'], 2);
        $dateStr = Carbon::parse($data['date'])->toDateString();

        // Guard: tidak boleh melebihi sisa (opsional, bisa di service recalc juga)
        $invoice->refresh();
        $remain = max(0, (float) $invoice->grand_total - (float) $invoice->payments()->sum('amount'));
        if ($amount - $remain > 0.00001) {
            return back()->withErrors('Nominal melebihi sisa hutang.')->withInput();
        }

        DB::transaction(function () use ($invoice, $data, $amount, $dateStr, $journal, $pps) {
            // Simpan payment
            /** @var PurchasePayment $payment */
            $payment = PurchasePayment::create([
                'purchase_invoice_id' => $invoice->id,
                'date' => $dateStr,
                'amount' => $amount,
                'method' => $data['method'], // cash|bank|transfer|other
                'ref_no' => $data['ref_no'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Voucher jurnal pembayaran (terpisah dari voucher invoice)
            $journal->postPaymentPurchase(
                refCode: $invoice->code . '/PAY-' . $payment->id,
                date: $dateStr,
                amount: $amount,
                method: $payment->method,
                memo: $payment->note
            );

            // Recalc status paid/partial/unpaid
            $pps->recalc($invoice->fresh('payments'));
        });

        return back()->with('success', 'Pembayaran tersimpan.');
    }

    /** Hapus pembayaran + reversal jurnal */
    public function destroy(
        Request $r,
        PurchaseInvoice $invoice,
        PurchasePayment $payment,
        JournalService $journal,
        PurchasePaymentService $pps
    ) {
        // (opsional) otorisasi/guard relasi
        if ($payment->purchase_invoice_id !== $invoice->id) {
            abort(404);
        }

        DB::transaction(function () use ($invoice, $payment, $journal, $pps) {
            // Reversal jurnal pembayaran
            $journal->reversePaymentPurchase(
                refCode: $invoice->code . '/PAY-' . $payment->id,
                date: $payment->date->toDateString(),
                amount: (float) $payment->amount,
                method: $payment->method,
                memo: 'Reversal delete payment'
            );

            $payment->delete();

            // Recalc kembali
            $pps->recalc($invoice->fresh('payments'));
        });

        return back()->with('success', 'Pembayaran dihapus & jurnal di-reversal.');
    }
}
