<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_date',
        'amount_decimal',
        'amount_string',
        'category',
        'type',
        'handled_by',
        'from_account',
        'to_account',
        'description',
        'invoice',
        'archived',
    ];

    // رابطه تراکنش با کاربر
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
