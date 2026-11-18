<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanteenTransactionItem extends Model
{
    protected $fillable = [
        'canteen_transaction_id',
        'canteen_item_id',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
        'total',
    ];

    public function canteenTransaction()
    {
        return $this->belongsTo(CanteenTransaction::class);
    }

    public function canteenItem()
    {
        return $this->belongsTo(CanteenItem::class);
    }
}
