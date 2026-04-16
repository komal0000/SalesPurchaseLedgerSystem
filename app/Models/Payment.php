<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Payment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            if (filled($payment->sale_id) && filled($payment->purchase_id)) {
                throw new InvalidArgumentException('Payment cannot link to both a sale and a purchase.');
            }
        });
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function resolvedPaymentKind(): string
    {
        if (filled($this->payment_kind)) {
            return (string) $this->payment_kind;
        }

        if (filled($this->sale_id)) {
            return 'receivable';
        }

        if (filled($this->purchase_id)) {
            return 'payable';
        }

        return 'advance';
    }

    public function resolvedAdvanceDirection(): ?string
    {
        if ($this->resolvedPaymentKind() !== 'advance') {
            return null;
        }

        if (filled($this->advance_direction)) {
            return (string) $this->advance_direction;
        }

        return $this->type === 'given' ? 'paid' : 'received';
    }
}
