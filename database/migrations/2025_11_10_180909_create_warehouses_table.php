<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // KONTRAKAN, RUMAH
            $t->string('name');
            $t->timestamps();
        });

        // Isi kolom type berdasarkan prefix code gudang yang sudah ada
        DB::table('warehouses')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $code = $row->code ?? '';
                $type = 'other';

                // silakan sesuaikan pola kodenya dengan yang kamu pakai sekarang
                if (strpos($code, 'RAW') === 0) {
                    $type = 'raw';
                } elseif (strpos($code, 'WIP-SEW') === 0) {
                    $type = 'wip_sewing';
                } elseif (strpos($code, 'WIP-FIN') === 0) {
                    $type = 'wip_fin';
                } elseif (strpos($code, 'FG') === 0) {
                    $type = 'fg';
                } elseif (strpos($code, 'SEW-EXT-') === 0) {
                    $type = 'external_sew';
                }

                DB::table('warehouses')
                    ->where('id', $row->id)
                    ->update(['type' => $type]);
            }
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
