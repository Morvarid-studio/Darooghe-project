<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBalanceHistory extends Model
{
    protected $table = 'employee_balances_history';

    protected $fillable = [
        'user_id',
        'total_costs',
        'total_received',
        'balance',
        'month'
    ];

    protected $casts = [
        'total_costs' => 'decimal:2',
        'total_received' => 'decimal:2',
        'balance' => 'decimal:2',
        'month' => 'date:Y-m', // فقط ماه
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
