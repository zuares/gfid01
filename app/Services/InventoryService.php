<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Mutasi stok per LOT per gudang (low-level).
     *
     * Dipakai oleh:
     * - PurchaseController@post()  → PURCHASE_IN
     * - transfer()                → TRANSFER_IN / TRANSFER_OUT
     */
    /**
     * Mutasi stok per LOT (untuk bahan baku kain, dll).
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
        ?string $date = null,
        ?string $category = null
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
            $date,
            $category
        ) {
            // 1) catat di inventory_mutations
            DB::table('inventory_mutations')->insert([
                'warehouse_id' => $warehouseId,
                'category' => $category,
                'lot_id' => $lotId,
                'item_id' => $lot->item_id,
                'item_code' => $lot->item_code,
                'type' => $type,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'unit' => $unit,
                'ref_code' => $refCode,
                'note' => $note,
                'date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2) hitung saldo per gudang+LOT+unit
            $agg = DB::table('inventory_mutations')
                ->selectRaw('COALESCE(SUM(qty_in - qty_out), 0) as qty')
                ->where('warehouse_id', $warehouseId)
                ->where('lot_id', $lotId)
                ->where('unit', $unit)
                ->first();

            $qtyNow = (float) ($agg->qty ?? 0);

            // 3) update / insert inventory_stocks (per gudang+LOT+unit)
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
     * Wajib di $data:
     * - warehouse_id
     * - lot_id
     * - unit
     * - qty
     *
     * Optional:
     * - type (default: FG_IN)
     * - ref_code
     * - note
     * - date (Y-m-d, default: today)
     * - category
     *
     * NOTE:
     * item_id & item_code akan diambil dari LOT agar konsisten.
     */
    public static function addStockLot(array $data): InventoryStock
    {
        $self = app(self::class);

        $warehouseId = $data['warehouse_id'];
        $lotId = $data['lot_id'];
        $unit = $data['unit'];
        $qty = $data['qty'];
        $type = $data['type'] ?? 'FG_IN';
        $refCode = $data['ref_code'] ?? null;
        $note = $data['note'] ?? null;
        $date = $data['date'] ?? now()->toDateString();
        $category = $data['category'] ?? null;

        $self->mutate(
            warehouseId: $warehouseId,
            lotId: $lotId,
            type: $type,
            qtyIn: $qty,
            qtyOut: 0.0,
            unit: $unit,
            refCode: $refCode,
            note: $note,
            date: $date,
            category: $category,
        );

        // Setelah mutate, stok sudah ke-update di inventory_stocks.
        return InventoryStock::where('warehouse_id', $warehouseId)
            ->where('lot_id', $lotId)
            ->where('unit', $unit)
            ->firstOrFail();
    }

    /**
     * Mutasi per ITEM (tanpa LOT).
     *
     * Dipakai untuk Finished Goods:
     * - stok per gudang + item + unit (lot_id = null)
     * - item_id & item_code diambil dari tabel items, bukan dari LOT
     */
    /**
     * Mutasi stok per ITEM (tanpa LOT) → dipakai:
     * - WIP hasil cutting (K7BLK, K5BLK, dst)
     * - WIP sewing, FG, dll
     */
    public function mutateItem(
        int $warehouseId,
        int $itemId,
        string $itemCode,
        string $type,
        float $qtyIn,
        float $qtyOut,
        string $unit,
        ?string $refCode = null,
        ?string $note = null,
        ?string $date = null,
        ?string $category = null
    ): void {
        $date = $date ?: now()->toDateString();

        DB::transaction(function () use (
            $warehouseId,
            $itemId,
            $itemCode,
            $type,
            $qtyIn,
            $qtyOut,
            $unit,
            $refCode,
            $note,
            $date,
            $category
        ) {
            // 1) catat di inventory_mutations (lot_id = null)
            DB::table('inventory_mutations')->insert([
                'warehouse_id' => $warehouseId,
                'category' => $category,
                'lot_id' => null,
                'item_id' => $itemId,
                'item_code' => $itemCode,
                'type' => $type,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'unit' => $unit,
                'ref_code' => $refCode,
                'note' => $note,
                'date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2) hitung saldo per gudang+ITEM+unit (lot_id IS NULL)
            $agg = DB::table('inventory_mutations')
                ->selectRaw('COALESCE(SUM(qty_in - qty_out), 0) as qty')
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->whereNull('lot_id')
                ->where('unit', $unit)
                ->first();

            $qtyNow = (float) ($agg->qty ?? 0);

            // 3) update / insert inventory_stocks (per gudang+ITEM+unit, lot_id = null)
            $existing = DB::table('inventory_stocks')
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->whereNull('lot_id')
                ->where('unit', $unit)
                ->first();

            if ($existing) {
                DB::table('inventory_stocks')
                    ->where('id', $existing->id)
                    ->update([
                        'item_code' => $itemCode,
                        'qty' => $qtyNow,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('inventory_stocks')->insert([
                    'warehouse_id' => $warehouseId,
                    'lot_id' => null,
                    'item_id' => $itemId,
                    'item_code' => $itemCode,
                    'unit' => $unit,
                    'qty' => $qtyNow,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Tambah stok (mutasi IN) per gudang + ITEM (tanpa LOT).
     *
     * Wajib di $data:
     * - warehouse_id
     * - item_id
     * - item_code
     * - unit
     * - qty
     *
     * Optional:
     * - type (default: FG_IN)
     * - ref_code
     * - note
     * - date (Y-m-d, default: today)
     * - category
     */
    public static function addStockItem(array $data): InventoryStock
    {
        $self = app(self::class);

        $warehouseId = $data['warehouse_id'];
        $itemId = $data['item_id'];
        $itemCode = $data['item_code'];
        $unit = $data['unit'];
        $qty = $data['qty'];
        $type = $data['type'] ?? 'FG_IN';
        $refCode = $data['ref_code'] ?? null;
        $note = $data['note'] ?? null;
        $date = $data['date'] ?? now()->toDateString();
        $category = $data['category'] ?? null;

        $self->mutateItem(
            warehouseId: $warehouseId,
            itemId: $itemId,
            itemCode: $itemCode,
            type: $type,
            qtyIn: $qty,
            qtyOut: 0.0,
            unit: $unit,
            refCode: $refCode,
            note: $note,
            date: $date,
            category: $category,
        );

        return InventoryStock::where('warehouse_id', $warehouseId)
            ->whereNull('lot_id')
            ->where('item_id', $itemId)
            ->where('unit', $unit)
            ->firstOrFail();
    }

    /**
     * Kurangi stok (mutasi OUT) per gudang + LOT.
     *
     * Dipakai untuk:
     * - pemakaian WIP
     * - pengeluaran FG untuk packing / jual, dll.
     */
    public static function reduceStockLot(array $data): InventoryStock
    {
        $self = app(self::class);

        $warehouseId = $data['warehouse_id'];
        $lotId = $data['lot_id'];
        $unit = $data['unit'];
        $qty = $data['qty'];
        $type = $data['type'] ?? 'FG_OUT';
        $refCode = $data['ref_code'] ?? null;
        $note = $data['note'] ?? null;
        $date = $data['date'] ?? now()->toDateString();
        $category = $data['category'] ?? null;
        // dd($qty);
        // Pastikan stok cukup
        $stock = InventoryStock::where('warehouse_id', $warehouseId)
            ->where('lot_id', $lotId)
            ->where('unit', $unit)
            ->lockForUpdate()
            ->first();

        if (!$stock || $stock->qty < $qty) {
            throw new \RuntimeException('Stok tidak mencukupi untuk mutasi OUT.');
        }

        $self->mutate(
            warehouseId: $warehouseId,
            lotId: $lotId,
            type: $type,
            qtyIn: 0.0,
            qtyOut: $qty,
            unit: $unit,
            refCode: $refCode,
            note: $note,
            date: $date,
            category: $category,
        );

        // Ambil stok terbaru
        return InventoryStock::where('warehouse_id', $warehouseId)
            ->where('lot_id', $lotId)
            ->where('unit', $unit)
            ->firstOrFail();
    }

    /**
     * Transfer stok antar gudang, per LOT.
     *
     * Akan membuat:
     * - Mutasi TRANSFER_OUT di gudang asal
     * - Mutasi TRANSFER_IN di gudang tujuan
     */
    public function transfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $lotId,
        float $qty,
        string $unit,
        ?string $refCode = null,
        ?string $note = null,
        ?string $date = null, // YYYY-MM-DD
        ?string $category = null
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
            category: $category,
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
            category: $category,
        );
    }

    public static function transferForPacking(array $data): void
    {
        $self = app(self::class);

        $self->transfer(
            fromWarehouseId: $data['from_warehouse_id'],
            toWarehouseId: $data['to_warehouse_id'],
            lotId: (int) $data['lot_id'],
            qty: (float) $data['qty'],
            unit: $data['unit'],
            refCode: $data['ref_code'] ?? null,
            note: $data['note'] ?? null,
            date: $data['date'] ?? null,
            // category: $data['category'] ?? 'fg', // default kategori FG
        );
    }

    /**
     * Helper khusus: transfer stok dari WIP-CUT → WIP-SEW (hasil cutting siap dijahit).
     *
     * data:
     * - from_warehouse_id  (gudang WIP-CUT)
     * - to_warehouse_id    (gudang WIP-SEW)
     * - lot_id
     * - qty
     * - unit
     * - ref_code   (opsional, misal kode batch QC cutting)
     * - note       (opsional)
     * - date       (opsional, Y-m-d)
     * - category   (opsional, default 'wip')
     */
    public static function transferCuttingToSew(array $data): void
    {
        $self = app(self::class);

        $self->transfer(
            fromWarehouseId: $data['from_warehouse_id'],
            toWarehouseId: $data['to_warehouse_id'],
            lotId: (int) $data['lot_id'],
            qty: (float) $data['qty'],
            unit: $data['unit'],
            refCode: $data['ref_code'] ?? null,
            note: $data['note'] ?? 'Transfer CUT → SEW',
            date: $data['date'] ?? null,
            category: $data['category'] ?? 'wip',
        );
    }

    /**
     * Convenience wrapper: addStock() berbasis LOT (untuk memudahkan pemanggilan).
     * Wajib: warehouse_id, lot_id, unit, qty
     */
    public static function addStock(array $data): InventoryStock
    {
        return self::addStockLot($data);
    }

    /**
     * Convenience wrapper: removeStock() berbasis LOT.
     */
    public static function removeStock(array $data): InventoryStock
    {
        return self::reduceStockLot($data);
    }

    /**
     * Transfer stok LOT dari satu gudang ke gudang lain.
     *
     * data:
     * - from_warehouse_id
     * - to_warehouse_id
     * - lot_id
     * - item_id
     * - item_code
     * - unit
     * - qty
     * - date
     * - ref_code
     * - category (rawmaterial / wip / fg)
     */
    // public static function transferLot(array $data): void
    // {
    //     DB::transaction(function () use ($data) {

    //         $qty = (float) $data['qty'];

    //         // 1) Kurangi stok di gudang asal
    //         static::adjustStockLot([
    //             'warehouse_id' => $data['from_warehouse_id'],
    //             'lot_id' => $data['lot_id'],
    //             'item_id' => $data['item_id'],
    //             'item_code' => $data['item_code'],
    //             'unit' => $data['unit'],
    //             'qty_delta' => -$qty,
    //         ]);

    //         // Mutasi OUT
    //         InventoryMutation::create([
    //             'warehouse_id' => $data['from_warehouse_id'],
    //             'lot_id' => $data['lot_id'],
    //             'item_id' => $data['item_id'],
    //             'item_code' => $data['item_code'],
    //             'type' => 'TRANSFER_OUT',
    //             'category' => $data['category'] ?? null,
    //             'qty_in' => 0,
    //             'qty_out' => $qty,
    //             'unit' => $data['unit'],
    //             'ref_code' => $data['ref_code'] ?? null,
    //             'date' => $data['date'],
    //         ]);

    //         // 2) Tambah stok di gudang tujuan
    //         static::adjustStockLot([
    //             'warehouse_id' => $data['to_warehouse_id'],
    //             'lot_id' => $data['lot_id'],
    //             'item_id' => $data['item_id'],
    //             'item_code' => $data['item_code'],
    //             'unit' => $data['unit'],
    //             'qty_delta' => $qty,
    //         ]);

    //         // Mutasi IN
    //         InventoryMutation::create([
    //             'warehouse_id' => $data['to_warehouse_id'],
    //             'lot_id' => $data['lot_id'],
    //             'item_id' => $data['item_id'],
    //             'item_code' => $data['item_code'],
    //             'type' => 'TRANSFER_IN',
    //             'category' => $data['category'] ?? null,
    //             'qty_in' => $qty,
    //             'qty_out' => 0,
    //             'unit' => $data['unit'],
    //             'ref_code' => $data['ref_code'] ?? null,
    //             'date' => $data['date'],
    //         ]);
    //     });
    // }

    /**
     * Helper untuk update/inisialisasi saldo stok per LOT & gudang.
     */
    // public static function adjustStockLot(array $data): void
    // {
    //     $stock = InventoryStock::firstOrNew([
    //         'warehouse_id' => $data['warehouse_id'],
    //         'lot_id' => $data['lot_id'],
    //         'unit' => $data['unit'],
    //     ]);

    //     if (!$stock->exists) {
    //         $stock->item_id = $data['item_id'];
    //         $stock->item_code = $data['item_code'];
    //         $stock->qty = 0;
    //     }

    //     $stock->qty = $stock->qty + (float) $data['qty_delta'];
    //     $stock->save();
    // }

}
