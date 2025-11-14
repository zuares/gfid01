<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'role',
        'active',
        'phone',
        'address',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /* ============================================================
    |  SCOPES
    |============================================================ */

    // Aktif saja
    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    // Filter per role: cutting, sewing, dll
    public function scopeRole($q, string $role)
    {
        return $q->where('role', $role);
    }

    /* ============================================================
    |  RELATIONSHIPS
    |============================================================ */

    /**
     * Relasi ke tabel piece-rate
     * (akan kamu gunakan untuk payroll per pcs)
     *
     * employee_piece_rates:
     *  - employee_id
     *  - item_id
     *  - rate_cutting
     *  - rate_sewing
     */
    public function pieceRates()
    {
        return $this->hasMany(EmployeePieceRate::class);
    }

    /**
     * Relasi ke pekerjaan cutting harian
     * vendor_cutting_jobs:
     *   - id
     *   - code
     *   - date
     *   - employee_id
     *   - external_transfer_id
     */
    public function cuttingJobs()
    {
        return $this->hasMany(VendorCuttingJob::class, 'employee_id');
    }

    /**
     * Relasi ke pekerjaan sewing nanti
     * sewing_jobs:
     *   - id
     *   - code
     *   - date
     *   - employee_id
     *   - production_batch_id
     */
    public function sewingJobs()
    {
        return $this->hasMany(SewingJob::class, 'employee_id');
    }

    /* ============================================================
    |  ACCESSORS
    |============================================================ */

    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
