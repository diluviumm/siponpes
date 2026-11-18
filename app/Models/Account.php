<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'account_name',
        'type',
    ];

    public function journalDetails()
    {
        return $this->hasMany(JournalDetail::class);
    }
}
