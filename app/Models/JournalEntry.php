<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = ['code', 'date', 'ref_code', 'memo'];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }
}
