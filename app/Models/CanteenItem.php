<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CanteenItem extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'canteen_id',
        'name',
        'price',
        'unit',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function canteen()
    {
        return $this->belongsTo(Canteen::class);
    }
}
