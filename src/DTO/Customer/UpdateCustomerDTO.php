<?php

declare(strict_types=1);

namespace App\DTO\Customer;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCustomerDTO
{
    #[Assert\Length(max: 255)]
    public ?string $firstName = null;

    #[Assert\Length(max: 255)]
    public ?string $lastName = null;

    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public ?string $email = null;

    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    public function __construct(?string $firstName = null, ?string $lastName = null, ?string $email = null, ?string $phone = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
    }
}
