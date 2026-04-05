<?php

namespace App\Enum;

enum CustomerStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CLOSED => 'Closed',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ACTIVE    => 'success',
            self::SUSPENDED => 'warning',
            self::CLOSED    => 'secondary',
        };
    }

}