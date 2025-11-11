<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $fillable = ['journal_entry_id', 'account_id', 'debit', 'credit', 'note'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
