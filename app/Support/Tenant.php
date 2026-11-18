<?php

namespace App\Support;

/**
 * Konteks tenant (sekolah) aktif pada request berjalan.
 */
class Tenant
{
    private static ?int $branchId = null;

    public static function set(?int $branchId): void
    {
        static::$branchId = $branchId;
    }

    public static function id(): ?int
    {
        return static::$branchId;
    }

    public static function active(): bool
    {
        return static::$branchId !== null;
    }
}
