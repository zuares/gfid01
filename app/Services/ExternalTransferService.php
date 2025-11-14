<?php

namespace App\Services;

use App\Models\ExternalTransfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExternalTransferService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function send(ExternalTransfer $t, ?string $date = null): void
    {
        if ($t->status !== 'draft') {
            throw new \RuntimeException("Hanya dokumen draft yang bisa dikirim.");
        }

        $t->load('lines');

        if ($t->lines->isEmpty()) {
            throw new \RuntimeException("Dokumen {$t->code} tidak punya detail LOT.");
        }

        $date = $date ?: $t->date?->toDateString() ?: now()->toDateString();
        $refNote = "ExternalTransfer {$t->code}";

        DB::transaction(function () use ($t, $date, $refNote) {
            foreach ($t->lines as $ln) {
                $this->inventory->transfer(
                    fromWarehouseId: $t->from_warehouse_id,
                    toWarehouseId: $t->to_warehouse_id,
                    lotId: $ln->lot_id,
                    qty: (float) $ln->qty,
                    unit: $ln->uom,
                    refCode: $t->code,
                    note: $refNote,
                    date: $date,
                );
            }

            $t->update([
                'status' => 'sent',
            ]);
        });
    }

    public function receive(ExternalTransfer $t, array $payload): void
    {
        DB::transaction(function () use ($t, $payload) {
            $date = isset($payload['date']) ? Carbon::parse($payload['date']) : now();
            $note = $payload['note'] ?? null;
            $linesInput = $payload['lines'] ?? [];

            $prefix = 'RCV-EXT';

            $countToday = DB::table('external_receipts')
                ->where('external_transfer_id', $t->id)
                ->whereDate('date', $date->toDateString())
                ->count();

            $sequence = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $date->format('ymd') . '-' . $t->operator_code . '-' . $sequence;

            $receiptId = DB::table('external_receipts')->insertGetId([
                'external_transfer_id' => $t->id,
                'code' => $code,
                'date' => $date->toDateString(),
                'status' => 'draft',
                'note' => $note,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $allFullyReceived = true;

            foreach ($linesInput as $row) {
                $transferLineId = (int) ($row['transfer_line_id'] ?? 0);
                $recQty = max(0, (float) ($row['received_qty'] ?? 0));
                $defQty = max(0, (float) ($row['defect_qty'] ?? 0));

                if ($transferLineId <= 0 || ($recQty <= 0 && $defQty <= 0)) {
                    continue;
                }

                $line = DB::table('external_transfer_lines')
                    ->where('id', $transferLineId)
                    ->where('external_transfer_id', $t->id)
                    ->lockForUpdate()
                    ->first();

                if (!$line) {
                    continue;
                }

                $already = (float) $line->received_qty + (float) $line->defect_qty;
                $total = (float) $line->qty;
                $remaining = max(0, $total - $already);

                if ($remaining <= 0) {
                    continue;
                }

                $newTotal = min($remaining, $recQty + $defQty);
                if ($newTotal <= 0) {
                    continue;
                }

                $useRec = min($recQty, $newTotal);
                $useDef = min($defQty, $newTotal - $useRec);

                DB::table('external_receipt_lines')->insert([
                    'external_receipt_id' => $receiptId,
                    'transfer_line_id' => $transferLineId,
                    'received_qty' => $useRec,
                    'defect_qty' => $useDef,
                    'note' => $row['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('external_transfer_lines')
                    ->where('id', $transferLineId)
                    ->update([
                        'received_qty' => (float) $line->received_qty + $useRec,
                        'defect_qty' => (float) $line->defect_qty + $useDef,
                        'updated_at' => now(),
                    ]);

                $sisaSesudah = $remaining - $newTotal;
                if ($sisaSesudah > 0.0000001) {
                    $allFullyReceived = false;
                }
            }

            if ($allFullyReceived) {
                $t->status = 'received';
            } else {
                if (in_array($t->status, ['draft', 'sent', 'partially_received'])) {
                    $t->status = 'partially_received';
                }
            }

            $t->save();
        });
    }

    public function post(ExternalTransfer $t, ?string $memo = null): void
    {
        if ($t->status !== 'received') {
            return;
        }

        // Di sini nanti tinggal panggil JournalService kalau sudah siap
        $t->status = 'posted';
        $t->save();
    }
}
