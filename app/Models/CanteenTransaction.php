<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class CanteenTransaction extends Model
{
    use Auditable;

    protected $fillable = [
        'wallet_transaction_id',
        'canteen_id',
        'payment_method',
        'branch_id',
        'total',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function canteen()
    {
        return $this->belongsTo(Canteen::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'branch_id');
    }

    public function items()
    {
        return $this->hasMany(CanteenTransactionItem::class);
    }
}
