<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class SppTariff extends Model
{
    use Auditable, TenantScoped;

    protected $fillable = [
        'kelas_id',
        'angkatan',
        'type',
        'periode_type',
        'branch_id',
        'amount',
        'academic_year',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'branch_id');
    }
}
