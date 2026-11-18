<?php

namespace App\Support;

class CanteenUnits
{
    /** Satuan yang umum untuk makanan & minuman yang dijual di kantin. */
    public const UNITS = [
        'pcs',
        'pack',
        'porsi',
        'gelas',
        'cup',
        'botol',
        'sachet',
        'kotak',
        'bungkus',
        'mangkuk',
        'tusuk',
        'potong',
    ];

    public static function all(): array
    {
        return self::UNITS;
    }
}
