<?php

declare(strict_types=1);

namespace App\Exception;

use App\Enum\CustomerStatus;

class InvalidStatusTransitException extends DomainException
{
    public static function fromTo(CustomerStatus $from, CustomerStatus $to): self
    {
        return new self(sprintf(
            'Invalid status transition: "%s" → "%s".',
            $from->value,
            $to->value
        ));
    }
}
