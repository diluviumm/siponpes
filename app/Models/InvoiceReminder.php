<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class InvoiceReminder extends Model
{
    use TenantScoped;

    protected $fillable = [
        'invoice_id',
        'channel',
        'message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    protected static function tenantScopeMethod(): string
    {
        return 'whereHas';
    }

    protected static function applyTenantWhereHas($builder, int $branchId): void
    {
        $builder->whereHas('invoice.santri', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
