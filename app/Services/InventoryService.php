<?php

namespace App\Services;

use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Mutasi stok per LOT per gudang.
     *
     * Dipakai oleh:
     * - PurchaseController@post()           → PURCHASE_IN
     * - transfer()                         → TRANSFER_IN / TRANSFER_OUT
     */
    public function mutate(
        int $warehouseId,
        int $lotId,
        string $type,
        float $qtyIn,
        float $qtyOut,
        string $unit,
        ?string $refCode = null,
        ?string $note = null,
        ?string $date = null// YYYY-MM-DD
    ): void {
        $date = $date ?: now()->toDateString();

        // 🔹 Ambil info LOT + ITEM
        $lot = DB::table('lots')
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->where('lots.id', $lotId)
            ->select(
                'lots.id as lot_id',
                'lots.item_id',
                'items.code as item_code'
            )
            ->first();

        if (!$lot) {
            throw new \RuntimeException("LOT {$lotId} tidak ditemukan.");
        }

        DB::transaction(function () use (
            $warehouseId,
            $lot,
            $lotId,
            $type,
            $qtyIn,
            $qtyOut,
            $unit,
            $refCode,
            $note,
            $date
        ) {
            // 1) INSERT ke inventory_mutations
            DB::table('inventory_mutations')->insert([
                'warehouse_id' => $warehouseId,
                'lot_id' => $lotId,
                'item_id' => $lot->item_id,
                'item_code' => $lot->item_code,
                'type' => $type, // PURCHASE_IN / TRANSFER_OUT / TRANSFER_IN / dll
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'unit' => $unit,
                'ref_code' => $refCode,
                'note' => $note,
                'date' => $date, // ← pakai $date (tipe DATE)
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2) HITUNG ULANG saldo stok untuk kombinasi (warehouse_id + lot_id + unit)
            $agg = DB::table('inventory_mutations')
                ->selectRaw('COALESCE(SUM(qty_in - qty_out), 0) as qty')
                ->where('warehouse_id', $warehouseId)
                ->where('lot_id', $lotId)
                ->where('unit', $unit)
                ->first();

            $qtyNow = (float) ($agg->qty ?? 0);

            // 3) UPDATE / INSERT ke inventory_stocks (per gudang + LOT)
            $existing = DB::table('inventory_stocks')
                ->where('warehouse_id', $warehouseId)
                ->where('lot_id', $lotId)
                ->where('unit', $unit)
                ->first();

            if ($existing) {
                DB::table('inventory_stocks')
                    ->where('id', $existing->id)
                    ->update([
                        'item_id' => $lot->item_id,
                        'item_code' => $lot->item_code,
                        'qty' => $qtyNow,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('inventory_stocks')->insert([
                    'warehouse_id' => $warehouseId,
                    'lot_id' => $lotId,
                    'item_id' => $lot->item_id,
                    'item_code' => $lot->item_code,
                    'unit' => $unit,
                    'qty' => $qtyNow,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Tambah stok (mutasi IN) per gudang + LOT.
     *
     * $data yang dibutuhkan:
     * - warehouse_id
     * - lot_id
     * - item_id
     * - item_code
     * - unit          (pcs / kg / m)
     * - qty           (angka posistif)
     * - date          (Y-m-d)
     * - type          (string, misal: 'FG_IN')
     * - ref_code      (misal kode batch / dokumen)
     * - note          (opsional)
     */
    public static function addStockLot(array $data): InventoryStock
    {
        return DB::transaction(function () use ($data) {
            // 1) Catat mutasi IN
            InventoryMutation::create([
                'warehouse_id' => $data['warehouse_id'],
                'lot_id' => $data['lot_id'],
                'item_id' => $data['item_id'],
                'item_code' => $data['item_code'],
                'type' => $data['type'] ?? 'FG_IN',
                'qty_in' => $data['qty'],
                'qty_out' => 0,
                'unit' => $data['unit'],
                'ref_code' => $data['ref_code'] ?? null,
                'note' => $data['note'] ?? null,
                'date' => $data['date'], // cukup date saja
            ]);

            // 2) Update saldo di inventory_stocks
            $stock = InventoryStock::firstOrNew([
                'warehouse_id' => $data['warehouse_id'],
                'lot_id' => $data['lot_id'],
                'unit' => $data['unit'],
            ]);

            // Pastikan item_id & item_code sync
            $stock->item_id = $data['item_id'];
            $stock->item_code = $data['item_code'];

            $stock->qty = ($stock->qty ?? 0) + $data['qty'];
            $stock->save();

            return $stock;
        });
    }

    /**
     * Kurangi stok (mutasi OUT) per gudang + LOT.
     * (Berguna nanti untuk pengeluaran FG: packing, penjualan, dsb.)
     */
    public static function reduceStockLot(array $data): InventoryStock
    {
        return DB::transaction(function () use ($data) {
            $stock = InventoryStock::where('warehouse_id', $data['warehouse_id'])
                ->where('lot_id', $data['lot_id'])
                ->where('unit', $data['unit'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($stock->qty < $data['qty']) {
                throw new \RuntimeException('Stok tidak mencukupi untuk mutasi OUT.');
            }

            InventoryMutation::create([
                'warehouse_id' => $data['warehouse_id'],
                'lot_id' => $data['lot_id'],
                'item_id' => $stock->item_id,
                'item_code' => $stock->item_code,
                'type' => $data['type'] ?? 'FG_OUT',
                'qty_in' => 0,
                'qty_out' => $data['qty'],
                'unit' => $data['unit'],
                'ref_code' => $data['ref_code'] ?? null,
                'note' => $data['note'] ?? null,
                'date' => $data['date'],
            ]);

            $stock->qty -= $data['qty'];
            $stock->save();

            return $stock;
        });
    }
    /**
     * Transfer stok antar gudang, per LOT.
     *
     * Dipakai oleh:
     * - ExternalTransferService::send()
     *
     * Akan membuat:
     * - Mutasi TRANSFER_OUT di gudang asal
     * - Mutasi TRANSFER_IN di gudang tujuan
     * Dan otomatis update inventory_stocks di kedua gudang.
     */
    public function transfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $lotId,
        float $qty,
        string $unit,
        ?string $refCode = null,
        ?string $note = null,
        ?string $date = null// YYYY-MM-DD
    ): void {
        $date = $date ?: now()->toDateString();

        if ($qty <= 0) {
            return; // tidak ada yang dipindah
        }

        // 🔻 KELUAR dari gudang asal
        $this->mutate(
            warehouseId: $fromWarehouseId,
            lotId: $lotId,
            type: 'TRANSFER_OUT',
            qtyIn: 0.0,
            qtyOut: $qty,
            unit: $unit,
            refCode: $refCode,
            note: $note ? $note . ' (OUT)' : 'Transfer OUT',
            date: $date,
        );

        // 🔺 MASUK ke gudang tujuan
        $this->mutate(
            warehouseId: $toWarehouseId,
            lotId: $lotId,
            type: 'TRANSFER_IN',
            qtyIn: $qty,
            qtyOut: 0.0,
            unit: $unit,
            refCode: $refCode,
            note: $note ? $note . ' (IN)' : 'Transfer IN',
            date: $date,
        );
    }
}
