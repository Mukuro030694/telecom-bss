<?php

declare(strict_types=1);

namespace App\DTO\Tariff;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTariffDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    public float $monthlyPrice;

    #[Assert\NotNull]
    public bool $isActive;

    public function __construct(string $name, ?string $description, float $monthlyPrice, bool $isActive)
    {
        $this->name = $name;
        $this->description = $description;
        $this->monthlyPrice = $monthlyPrice;
        $this->isActive = $isActive;
    }
}