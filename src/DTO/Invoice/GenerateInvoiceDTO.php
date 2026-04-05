<?php

declare(strict_types=1);

namespace App\DTO\Invoice;

use Symfony\Component\Validator\Constraints as Assert;

class GenerateInvoiceDTO
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $customerId;

    /**
     * Формат: YYYY-MM (например: 2025-03)
     */
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{4}-(0[1-9]|1[0-2])$/',
        message: 'Period must be in format YYYY-MM'
    )]
    public string $period;
}