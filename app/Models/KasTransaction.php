<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasTransaction extends Model
{
    use Auditable, HasFactory, TenantScoped;

    protected $fillable = [
        'branch_id',
        'transaction_date',
        'type',
        'account_id',
        'amount',
        'description',
        'journal_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'branch_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
