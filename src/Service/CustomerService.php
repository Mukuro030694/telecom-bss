<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Customer\CreateCustomerDTO;
use App\DTO\Customer\UpdateCustomerDTO;
use App\Entity\Customer;
use App\Enum\CustomerStatus;
use App\Exception\CustomerNotFoundException;
use App\Exception\DomainException;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository      $customerRepository,
        private readonly EntityManagerInterface  $em,
        private readonly ValidatorInterface      $validator,
    ) {}

    public function create(CreateCustomerDTO $dto): Customer
    {
        $this->validate($dto);

        // Бизнес-правило: email должен быть уникальным
        $existing = $this->customerRepository->findByEmail($dto->email);
        if ($existing !== null) {
            throw new DomainException(
                "Такой email {$dto->email} уже используется."
            );
        }

        $customer = new Customer();
        $customer->setFirstName($dto->firstName);
        $customer->setLastName($dto->lastName);
        $customer->setEmail($dto->email);
        $customer->setPhone($dto->phone);

        $this->em->persist($customer);
        $this->em->flush();

        return $customer;
    }

    public function update(Customer $customer, UpdateCustomerDTO $dto): Customer
    {
        $this->validate($dto);

        // Бизнес-правило: нельзя редактировать закрытого клиента
        if ($customer->getStatus() === CustomerStatus::CLOSED) {
            throw new DomainException('Нельзя редактировать закрытого клиента.');
        }

        // Partial update — меняем только то что пришло
        if ($dto->firstName !== null) {
            $customer->setFirstName($dto->firstName);
        }
        if ($dto->lastName !== null) {
            $customer->setLastName($dto->lastName);
        }
        if ($dto->phone !== null) {
            $customer->setPhone($dto->phone);
        }

        $this->em->flush();

        return $customer;
    }

    public function suspend(Customer $customer): void
    {
        // Явная проверка перехода статуса
        if ($customer->getStatus() !== CustomerStatus::ACTIVE) {
            throw new DomainException(
                "Нельзя приостановить клиента, имеющего статус: {$customer->getStatus()->label()}"
            );
        }

        $customer->setStatus(CustomerStatus::SUSPENDED);
        $this->em->flush();
    }

    public function reactivate(Customer $customer): void
    {
        if ($customer->getStatus() !== CustomerStatus::SUSPENDED) {
            throw new DomainException('Реактивировать можно только приостановленного клиента.');
        }

        $customer->setStatus(CustomerStatus::ACTIVE);
        $this->em->flush();
    }

    public function close(Customer $customer): void
    {
        // Бизнес-правило: нельзя закрыть если есть неоплаченные счета
        foreach ($customer->getInvoices() as $invoice) {
            if ($invoice->getStatus()->isPending()) {
                throw new DomainException(
                    'Нельзя закрыть аккаунт c неоплаченными счетами .'
                );
            }
        }

        $customer->setStatus(CustomerStatus::CLOSED);
        $this->em->flush();
    }

    private function validate(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
    }
}