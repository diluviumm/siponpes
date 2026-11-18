<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Santri extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'nis',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_hp_wali',
        'email',
        'santri_card_token',
        'kelas_id',
        'angkatan',
        'branch_id',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'branch_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
