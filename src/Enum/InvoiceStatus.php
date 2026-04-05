<?php

declare(strict_types=1);

namespace App\Enum;

enum InvoiceStatus: string
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PAID => 'Paid',
            self::PENDING => 'Pending',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
            self::CANCELLED => 'secondary',
        };
    }

    // src/Enum/InvoiceStatus.php
    public function isPending(): bool
    {
        return self::PENDING === $this;
    }
}
