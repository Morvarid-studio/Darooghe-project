<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBalance extends Model
{
    protected $table = 'employee_balances'; // اسم ویو
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'user_id';

    protected $casts = [
        'total_costs' => 'decimal:2',
        'total_received' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
