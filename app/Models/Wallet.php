<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'santri_id',
        'balance',
        'daily_spending_limit',
    ];

    protected static function tenantScopeMethod(): string
    {
        return 'whereHas';
    }

    protected static function applyTenantWhereHas($builder, int $branchId): void
    {
        $builder->whereHas('santri', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });
    }

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
