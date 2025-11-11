<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'code', // contoh: KONTRAKAN, RUMAH
        'name', // nama lengkap: Gudang Kontrakan, Gudang Rumah
    ];

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
