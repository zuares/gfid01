<?php

namespace App\Models;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// pastikan ada

class SewingPickLine extends Model
{
    use HasFactory;

    protected $table = 'sewing_pick_lines';

    protected $fillable = [
        'sewing_pick_id',
        'wip_item_id',
        'lot_id',
        'item_id',
        'item_code',
        'qty',
        'unit',
        'notes',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    // Relations

    public function sewingPick()
    {
        return $this->belongsTo(SewingPick::class);
    }

    public function wipItem()
    {
        return $this->belongsTo(WipItem::class, 'wip_item_id');
    }

    // optional: jika ada model Lot atau Item, bisa ditambahkan
    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sewingReturns()
    {
        return $this->hasMany(SewingReturnLine::class, 'sewing_pick_line_id');
    }

}
