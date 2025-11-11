<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class LotCode
{
    // Untuk material: LOT-{ITEM}-{YYYYMMDD}-{SEQ}
    public static function nextMaterial(string $itemCode, \DateTimeInterface $date): string
    {
        $ymd = date('Ymd', $date->getTimestamp());
        $prefix = "LOT-{$itemCode}-{$ymd}-";
        $seq = 1;

        // cari max seq hari itu
        $exist = DB::table('lots')->where('code', 'like', $prefix . '%')->pluck('code')->all();
        foreach ($exist as $c) {
            if (preg_match('~^' . preg_quote($prefix, '~') . '(\d+)$~', $c, $m)) {
                $seq = max($seq, (int) $m[1] + 1);
            }
        }
        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    // Untuk produk jadi harian: LOT-{SKU}-{YYYYMMDD}-{SEQ}
    public static function nextFinished(string $sku, \DateTimeInterface $date): string
    {
        $ymd = date('Ymd', $date->getTimestamp());
        $prefix = "LOT-{$sku}-{$ymd}-";
        $seq = 1;

        $exist = DB::table('lots')->where('code', 'like', $prefix . '%')->pluck('code')->all();
        foreach ($exist as $c) {
            if (preg_match('~^' . preg_quote($prefix, '~') . '(\d+)$~', $c, $m)) {
                $seq = max($seq, (int) $m[1] + 1);
            }
        }
        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
