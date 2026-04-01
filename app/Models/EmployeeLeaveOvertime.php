<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveOvertime extends Model
{
    protected $guarded = [];

    protected $casts = [
        'bs_year' => 'integer',
        'bs_month' => 'integer',
        'leave_days' => 'decimal:2',
        'overtime_days' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
