<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'leave_fine_per_day' => 'decimal:2',
        'overtime_money_per_day' => 'decimal:2',
    ];
}
