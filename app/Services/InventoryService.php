<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function mutate(int $warehouseId, int $lotId, string $type, float $qtyIn, float $qtyOut, string $unit, ?string $refCode = null, ?string $note = null, ?string $dateTime = null): void
    {
        $dateTime = $dateTime ?: now()->toDateTimeString();

        DB::transaction(function () use ($warehouseId, $lotId, $type, $qtyIn, $qtyOut, $unit, $refCode, $note, $dateTime) {
            DB::table('inventory_mutations')->insert([
                'warehouse_id' => $warehouseId,
                'lot_id' => $lotId,
                'ref_code' => $refCode,
                'type' => $type,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'unit' => $unit,
                'date' => $dateTime,
                'note' => $note,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // upsert saldo
            $row = DB::table('inventory_stocks')->where([
                'warehouse_id' => $warehouseId,
                'lot_id' => $lotId,
            ])->first();

            $delta = $qtyIn - $qtyOut;
            if ($row) {
                DB::table('inventory_stocks')->where('id', $row->id)->update([
                    'qty' => max(0, ($row->qty + $delta)), // jaga tak negatif
                    'unit' => $unit,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('inventory_stocks')->insert([
                    'warehouse_id' => $warehouseId,
                    'lot_id' => $lotId,
                    'qty' => max(0, $delta),
                    'unit' => $unit,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function transfer(int $fromWarehouseId, int $toWarehouseId, int $lotId, float $qty, string $unit, ?string $refCode = null, ?string $note = null): void
    {
        DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $lotId, $qty, $unit, $refCode, $note) {
            $this->mutate($fromWarehouseId, $lotId, 'TRANSFER_OUT', 0, $qty, $unit, $refCode, $note);
            $this->mutate($toWarehouseId, $lotId, 'TRANSFER_IN', $qty, 0, $unit, $refCode, $note);
        });
    }
}
