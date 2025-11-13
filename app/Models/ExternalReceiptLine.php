<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalReceiptLine extends Model
{
    protected $fillable = ['external_receipt_id', 'transfer_line_id', 'received_qty', 'defect_qty', 'note'];

    public function receipt()
    {return $this->belongsTo(ExternalReceipt::class, 'external_receipt_id');}
    public function transferLine()
    {return $this->belongsTo(ExternalTransferLine::class, 'transfer_line_id');}
}
