<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{

    public const TYPE_RAW_MATERIAL = 'raw_material';
    public const TYPE_LOT_RAW = 'lot_raw';
    public const TYPE_WIP_CUT = 'wip_cut';
    public const TYPE_WIP_SEW = 'wip_sew';
    public const TYPE_WIP_SEW_EXT = 'wip_sew_ext';
    public const TYPE_WIP_FIN = 'wip_fin';
    public const TYPE_FG = 'fg';
    public const TYPE_REJECT_CUT = 'reject_cut';
    public const TYPE_REJECT_SEW = 'reject_sew';
    public const TYPE_REJECT_FIN = 'reject_fin';
    public const TYPE_WIP_REJECT = 'wip_reject';
    public const TYPE_RETURN_SEW = 'return_sew';
    public const TYPE_EXTERNAL_CUT = 'external_cut';
    public const TYPE_EXTERNAL_SEW = 'external_sew';

    protected $fillable = [
        'code',
        'name',
        'type',
        // kolom lain...
    ];

    public static function types(): array
    {
        return [
            self::TYPE_RAW_MATERIAL => 'Raw Material',
            self::TYPE_LOT_RAW => 'LOT Bahan',
            self::TYPE_WIP_CUT => 'WIP Cutting',
            self::TYPE_WIP_SEW => 'WIP Sewing',
            self::TYPE_WIP_SEW_EXT => 'WIP Sewing (Eksternal)',
            self::TYPE_WIP_FIN => 'WIP Finishing',
            self::TYPE_FG => 'Finished Goods',

            self::TYPE_REJECT_CUT => 'Reject Cutting',
            self::TYPE_REJECT_SEW => 'Reject Sewing',
            self::TYPE_REJECT_FIN => 'Reject Finishing',
            self::TYPE_WIP_REJECT => 'WIP Reject / Rework',

            self::TYPE_RETURN_SEW => 'Return Sewing',
            self::TYPE_EXTERNAL_CUT => 'Cutting Eksternal',
            self::TYPE_EXTERNAL_SEW => 'Sewing Eksternal',
        ];
    }

    public function typeLabel(): string
    {
        return static::types()[$this->type] ?? $this->type ?? '-';
    }

    /**
     * Relasi: stok per LOT di gudang ini
     */
    public function stocks()
    {
        return $this->hasMany(InventoryStock::class, 'warehouse_id');
    }

    /**
     * Relasi: mutasi barang (in/out/transfer)
     */
    public function mutations()
    {
        return $this->hasMany(InventoryMutation::class, 'warehouse_id');
    }

    /**
     * Relasi: pembelian (invoice) diterima ke gudang ini
     */
    public function purchases()
    {
        return $this->hasMany(PurchaseInvoice::class, 'warehouse_id');
    }

    /**
     * Scope pencarian cepat
     */
    public function scopeQ($q, $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        return $q->where('code', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%");
    }

    /**
     * Helper: cari gudang berdasarkan kode
     */
    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Helper: default gudang kontrakan
     */
    public static function kontrakan(): ?self
    {
        return static::where('code', 'KONTRAKAN')->first();
    }

    /**
     * Helper: default gudang rumah
     */
    public static function rumah(): ?self
    {
        return static::where('code', 'RUMAH')->first();
    }
}
