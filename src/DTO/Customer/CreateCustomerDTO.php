<?php

declare(strict_types=1);

namespace App\DTO\Customer;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCustomerDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email;

    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    public function __construct(string $firstName, string $lastName, string $email, ?string $phone = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
    }
}