<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class School extends Model
{
    use HasFactory;

    protected $table = 'branches';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'status',
        'api_key',
    ];

    public function santris()
    {
        return $this->hasMany(Santri::class, 'branch_id');
    }

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'branch_id');
    }

    public function sppTariffs()
    {
        return $this->hasMany(SppTariff::class, 'branch_id');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'branch_id');
    }

    public function canteens()
    {
        return $this->hasMany(Canteen::class, 'branch_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public static function generateApiKey(): string
    {
        return 'sk_'.Str::random(32);
    }
}
