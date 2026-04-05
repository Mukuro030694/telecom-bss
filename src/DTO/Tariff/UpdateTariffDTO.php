<?php

declare(strict_types=1);

namespace App\DTO\Tariff;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateTariffDTO
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[Assert\PositiveOrZero]
    public ?float $monthlyPrice = null;

    public ?bool $isActive = null;

    public function __construct(?string $name = null, ?string $description = null, ?float $monthlyPrice = null, ?bool $isActive = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->monthlyPrice = $monthlyPrice;
        $this->isActive = $isActive;
    }
}
