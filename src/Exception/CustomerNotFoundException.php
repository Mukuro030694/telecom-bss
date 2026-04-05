<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\Uid\Uuid;

class CustomerNotFoundException extends DomainException
{
    public static function byId(string|Uuid $id): self
    {
        return new self(sprintf(
            'Customer "%s" not found',
            (string) $id
        ));
    }
}