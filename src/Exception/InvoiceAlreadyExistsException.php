<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Customer;

class InvoiceAlreadyExistsException extends DomainException
{
    public static function forCustomerAndPeriod(
        Customer $customer,
        \DateTimeImmutable $period
    ): self {
        return new self(sprintf(
            'Invoice already exists for customer "%s" for period "%s".',
            $customer->getEmail(),
            $period->format('Y-m')
        ));
    }
}