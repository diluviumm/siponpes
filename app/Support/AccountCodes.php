<?php

namespace App\Support;

/**
 * Kode akun standar yang dipakai jurnal aplikasi.
 * Dijaga konsisten dengan akun bawaan pada migration create_accounts_table.
 */
class AccountCodes
{
    public const CASH = '1010';

    public const WALLET_LIABILITY = '2010';

    public const RETAINED_EARNINGS = '3010';

    public const CANTEEN_REVENUE = '4010';

    public const SPP_REVENUE = '4020';

    public const OTHER_REVENUE = '4030';

    public const EXPENSE_UTILITIES = '5010';

    public const EXPENSE_OPERATIONAL = '5020';

    public const EXPENSE_UNIFORM = '5030';

    public const EXPENSE_STAFF = '5040';

    public const EXPENSE_OTHER = '5050';
}
