<?php

namespace App\Models\Concerns;

use App\Support\Tenant;

trait TenantScoped
{
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            $branchId = static::resolveTenantBranchId();

            if (! $branchId) {
                return;
            }

            if (static::tenantScopeMethod() === 'column') {
                $builder->where(static::tenantColumn(), $branchId);
            } else {
                static::applyTenantWhereHas($builder, $branchId);
            }
        });
    }

    protected static function resolveTenantBranchId(): ?int
    {
        if (Tenant::active()) {
            return Tenant::id();
        }

        $user = auth()->user();

        if ($user && $user->isStaff() && $user->branch_id) {
            return (int) $user->branch_id;
        }

        return null;
    }

    public static function tenantColumn(): string
    {
        return 'branch_id';
    }

    protected static function tenantScopeMethod(): string
    {
        return 'column';
    }

    protected static function applyTenantWhereHas($builder, int $branchId): void
    {
        //
    }
}
