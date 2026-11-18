<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Canteen extends Model
{
    use Auditable, SoftDeletes, TenantScoped;

    protected $fillable = [
        'branch_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'branch_id');
    }

    public function items()
    {
        return $this->hasMany(CanteenItem::class);
    }

    public function activeItems()
    {
        return $this->hasMany(CanteenItem::class)->where('is_active', true);
    }

    public function transactions()
    {
        return $this->hasMany(CanteenTransaction::class);
    }
}
