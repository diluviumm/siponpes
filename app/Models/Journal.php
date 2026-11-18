<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use Auditable, HasFactory, TenantScoped;

    protected $fillable = [
        'transaction_date',
        'description',
        'reference',
        'branch_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(JournalDetail::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'branch_id');
    }
}
