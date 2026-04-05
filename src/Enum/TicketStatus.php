<?php

namespace App\Enum;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::OPEN => 'primary',
            self::IN_PROGRESS => 'warning',
            self::RESOLVED => 'success',
            self::CLOSED => 'secondary',
        };
    }

}