<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use Auditable, TenantScoped;

    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas',
        'wali_kelas',
        'kapasitas',
        'keterangan',
        'branch_id',
    ];

    public function santris()
    {
        return $this->hasMany(Santri::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'branch_id');
    }
}
