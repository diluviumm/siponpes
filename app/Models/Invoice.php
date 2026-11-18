<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use Auditable, TenantScoped;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'santri_id',
        'invoice_number',
        'total_amount',
        'status',
        'due_date',
        'period_type',
        'period_label',
        'period_number',
    ];

    protected $casts = [
        'due_date' => 'date',
        'period_number' => 'integer',
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

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function reminders()
    {
        return $this->hasMany(InvoiceReminder::class);
    }

    public function scopeFilterStatus($query, $status)
    {
        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function scopeFilterSchool($query, $branchId)
    {
        if ($branchId) {
            $query->whereHas('santri', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }

        return $query;
    }
}
