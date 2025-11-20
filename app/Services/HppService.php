<?php

namespace App\Services;

use App\Models\CuttingBundle;
use App\Models\ProductionCost;

class HppService
{
    /**
     * HPP per LOT
     *
     * - Ambil semua ProductionCost berdasarkan lot_id
     * - Stage lain (cutting, dll) pakai qty_base apa adanya
     * - KHUSUS raw_material:
     *     amount dijumlah, lalu dibagi dengan total pcs hasil cutting (qty_cut)
     */
    public function getLotCostSummary(int $lotId): array
    {
        // 🔹 Semua cost yang nempel ke LOT ini
        $costRows = ProductionCost::where('lot_id', $lotId)->get();

        $stages = [];

        // 1) Akumulasi qty_base & amount per stage (sementara)
        foreach ($costRows as $row) {
            $stage = $row->stage ?? 'unknown';

            if (!isset($stages[$stage])) {
                $stages[$stage] = [
                    'qty_base' => 0.0,
                    'amount' => 0.0,
                    'cost_per_unit' => 0.0,
                ];
            }

            $stages[$stage]['qty_base'] += (float) $row->qty_base;
            $stages[$stage]['amount'] += (float) $row->amount;
        }

        // 2) Hitung total PCS hasil cutting dari LOT ini
        $totalCutPcs = (float) CuttingBundle::where('lot_id', $lotId)->sum('qty_cut');

        // 3) Khusus RAW MATERIAL → override pakai totalCutPcs
        $rawAmount = $costRows
            ->where('stage', 'raw_material')
            ->sum('amount');

        if ($rawAmount > 0) {
            // Pastikan entry raw_material ada di array stages
            if (!isset($stages['raw_material'])) {
                $stages['raw_material'] = [
                    'qty_base' => 0.0,
                    'amount' => 0.0,
                    'cost_per_unit' => 0.0,
                ];
            }

            // Kalau ada pcs hasil cutting → pakai itu
            if ($totalCutPcs > 0) {
                $stages['raw_material']['qty_base'] = $totalCutPcs;
                $stages['raw_material']['amount'] = $rawAmount;
                $stages['raw_material']['cost_per_unit'] = $rawAmount / max(1, $totalCutPcs);
            } else {
                // fallback: tidak ada data cutting, pakai qty_base awal (misal kg)
                $qtyBaseRaw = (float) ($stages['raw_material']['qty_base'] ?? 0.0);
                $stages['raw_material']['amount'] = $rawAmount;
                $stages['raw_material']['cost_per_unit'] = $qtyBaseRaw > 0
                ? $rawAmount / $qtyBaseRaw
                : 0.0;
            }
        }

        // 4) Stage lain (cutting, dsb) → cost_per_unit = amount / qty_base
        foreach ($stages as $stage => &$row) {
            // raw_material sudah di-handle di atas
            if ($stage === 'raw_material') {
                continue;
            }

            $qty = (float) ($row['qty_base'] ?? 0.0);
            $amt = (float) ($row['amount'] ?? 0.0);

            $row['cost_per_unit'] = $qty > 0 ? $amt / $qty : 0.0;
        }
        unset($row);

        // 5) Referensi qty untuk total HPP LOT:
        //    - Kalau ada totalCutPcs → pakai itu
        //    - Kalau tidak → pakai qty_base terbesar dari stage apa pun
        $refQty = $totalCutPcs;

        if ($refQty <= 0) {
            $refQty = 0.0;
            foreach ($stages as $row) {
                $refQty = max($refQty, (float) ($row['qty_base'] ?? 0.0));
            }
        }

        $totalAmount = 0.0;
        foreach ($stages as $row) {
            $totalAmount += (float) ($row['amount'] ?? 0.0);
        }

        return [
            'total_amount' => $totalAmount,
            'total_qty_base' => $refQty,
            'cost_per_unit' => $refQty > 0 ? $totalAmount / $refQty : 0.0,
            'stages' => $stages,
        ];
    }

    /**
     * HPP per ITEM (final gabungan):
     *
     * - Raw material + cutting: ditarik dari LOT lewat CuttingBundle
     * - Sewing / finishing / packing: dari production_costs.item_id langsung
     */
    public function getItemCostSummary(int $itemId): array
    {
        $stages = [];

        // 1️⃣ Ambil semua cost yang nempel langsung ke ITEM ini
        $itemCosts = ProductionCost::where('item_id', $itemId)->get();

        foreach ($itemCosts as $row) {
            $stage = $row->stage ?? 'unknown';

            if (!isset($stages[$stage])) {
                $stages[$stage] = [
                    'qty_base' => 0.0,
                    'amount' => 0.0,
                    'cost_per_unit' => 0.0,
                ];
            }

            $stages[$stage]['qty_base'] += (float) $row->qty_base;
            $stages[$stage]['amount'] += (float) $row->amount;
        }

        // 2️⃣ Kontribusi RAW MATERIAL + CUTTING dari LOT via bundles
        $bundles = CuttingBundle::where('item_id', $itemId)->get(['lot_id', 'qty_cut']);

        $totalQtyItem = (float) $bundles->sum('qty_cut');

        if ($bundles->isNotEmpty() && $totalQtyItem > 0) {
            $byLot = $bundles
                ->groupBy('lot_id')
                ->map(function ($rows) {
                    return (float) $rows->sum('qty_cut');
                });

            $amountRawFromLot = 0.0;
            $amountCutFromLot = 0.0;

            foreach ($byLot as $lotId => $qtyFromLot) {
                if ($qtyFromLot <= 0) {
                    continue;
                }

                $lotSummary = $this->getLotCostSummary((int) $lotId);
                $lotStages = $lotSummary['stages'] ?? [];

                $cpuRaw = $lotStages['raw_material']['cost_per_unit'] ?? 0.0;
                $cpuCut = $lotStages['cutting']['cost_per_unit'] ?? 0.0;

                // Tambahkan kontribusi biaya ke item ini
                $amountRawFromLot += $cpuRaw * $qtyFromLot;
                $amountCutFromLot += $cpuCut * $qtyFromLot;
            }

            // Override / set stage raw_material di level ITEM
            if (!isset($stages['raw_material'])) {
                $stages['raw_material'] = [
                    'qty_base' => 0.0,
                    'amount' => 0.0,
                    'cost_per_unit' => 0.0,
                ];
            }
            $stages['raw_material']['qty_base'] = $totalQtyItem;
            $stages['raw_material']['amount'] = $amountRawFromLot;
            $stages['raw_material']['cost_per_unit'] = $amountRawFromLot / max(1, $totalQtyItem);

            // Override / set stage cutting di level ITEM
            if (!isset($stages['cutting'])) {
                $stages['cutting'] = [
                    'qty_base' => 0.0,
                    'amount' => 0.0,
                    'cost_per_unit' => 0.0,
                ];
            }
            $stages['cutting']['qty_base'] = $totalQtyItem;
            $stages['cutting']['amount'] = $amountCutFromLot;
            $stages['cutting']['cost_per_unit'] = $amountCutFromLot / max(1, $totalQtyItem);
        }

        // 3️⃣ Hitung cost_per_unit untuk stage lain (sewing, finishing, packing, dll)
        foreach ($stages as $stage => &$row) {
            // raw_material & cutting sudah di-set di atas (kalau lewat LOT)
            $qty = (float) ($row['qty_base'] ?? 0.0);
            $amt = (float) ($row['amount'] ?? 0.0);

            $row['cost_per_unit'] = $qty > 0 ? $amt / $qty : 0.0;
        }
        unset($row);

        // 4️⃣ Tentukan qty base total untuk HPP ITEM:
        //    - Kalau ada bundles → pakai total pcs item dari cutting
        //    - Kalau tidak → pakai qty_base terbesar dari stage apa pun
        $refQty = $totalQtyItem;

        if ($refQty <= 0) {
            $refQty = 0.0;
            foreach ($stages as $row) {
                $refQty = max($refQty, (float) ($row['qty_base'] ?? 0.0));
            }
        }

        $totalAmount = 0.0;
        foreach ($stages as $row) {
            $totalAmount += (float) ($row['amount'] ?? 0.0);
        }

        return [
            'total_amount' => $totalAmount,
            'total_qty_base' => $refQty,
            'cost_per_unit' => $refQty > 0 ? $totalAmount / $refQty : 0.0,
            'stages' => $stages,
        ];
    }
}
