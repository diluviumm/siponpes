<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use Auditable, TenantScoped;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'description',
        'reference_id',
    ];

    protected static function tenantScopeMethod(): string
    {
        return 'whereHas';
    }

    protected static function applyTenantWhereHas($builder, int $branchId): void
    {
        $builder->whereHas('wallet.santri', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
