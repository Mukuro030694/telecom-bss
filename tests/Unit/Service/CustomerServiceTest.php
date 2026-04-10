<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Customer\CreateCustomerDTO;
use App\DTO\Customer\UpdateCustomerDTO;
use App\Entity\Customer;
use App\Enum\CustomerStatus;
use App\Exception\DomainException;
use App\Repository\CustomerRepository;
use App\Service\CustomerService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerServiceTest extends UnitTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&CustomerRepository */
    private CustomerRepository $customerRepository;
    /** @var \PHPUnit\Framework\MockObject\MockObject&EntityManagerInterface */
    private EntityManagerInterface $em;
    /** @var \PHPUnit\Framework\MockObject\MockObject&ValidatorInterface */
    private ValidatorInterface $validator;
    private CustomerService $service;

    protected function setUp(): void
    {
        // Создаём моки для всех зависимостей
        // createMock() создаёт объект который имитирует интерфейс
        // но ничего реально не делает — пока мы не скажем что делать
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->em                 = $this->createMock(EntityManagerInterface::class);
        $this->validator          = $this->createMock(ValidatorInterface::class);

        $this->service = new CustomerService(
            $this->customerRepository,
            $this->em,
            $this->validator,
        );
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    /**
     * Happy path: клиент создаётся успешно.
     */
    public function testCreateSuccessfully(): void
    {
        $dto = new CreateCustomerDTO(
            firstName: 'Иван',
            lastName:  'Иванов',
            email:     'ivan@example.com',
            phone:     null,
        );

        // Говорим валидатору: ошибок нет
        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Говорим репозиторию: такого email ещё нет
        $this->customerRepository
            ->method('findByEmail')
            ->willReturn(null);

        // Проверяем что EntityManager получит вызов persist() и flush()
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $customer = $this->service->create($dto);

        self::assertSame('Иван', $customer->getFirstName());
        self::assertSame('Иванов', $customer->getLastName());
        self::assertSame('ivan@example.com', $customer->getEmail());
        self::assertSame(CustomerStatus::ACTIVE, $customer->getStatus());
    }

    /**
     * Email уже занят — должно бросить DomainException.
     */
    public function testCreateThrowsWhenEmailAlreadyExists(): void
    {
        $dto = new CreateCustomerDTO(
            firstName: 'Иван',
            lastName:  'Иванов',
            email:     'ivan@example.com',
            phone:     null,
        );

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Репозиторий возвращает существующего клиента
        $this->customerRepository
            ->method('findByEmail')
            ->willReturn(new Customer());

        // em->persist() и flush() не должны вызываться
        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $this->expectException(DomainException::class);

        $this->service->create($dto);
    }

    // -------------------------------------------------------------------------
    // suspend()
    // -------------------------------------------------------------------------

    /**
     * Приостановка активного клиента — успешный сценарий.
     */
    public function testSuspendActiveCustomer(): void
    {
        $customer = new Customer();
        $customer->setStatus(CustomerStatus::ACTIVE);

        $this->em->expects(self::once())->method('flush');

        $this->service->suspend($customer);

        self::assertSame(CustomerStatus::SUSPENDED, $customer->getStatus());
    }

    /**
     * Нельзя приостановить уже приостановленного клиента.
     */
    public function testSuspendAlreadySuspendedThrows(): void
    {
        $customer = new Customer();
        $customer->setStatus(CustomerStatus::SUSPENDED);

        $this->em->expects(self::never())->method('flush');

        $this->expectException(DomainException::class);

        $this->service->suspend($customer);
    }

    /**
     * Нельзя приостановить закрытого клиента.
     */
    public function testSuspendClosedCustomerThrows(): void
    {
        $customer = new Customer();
        $customer->setStatus(CustomerStatus::CLOSED);

        $this->expectException(DomainException::class);

        $this->service->suspend($customer);
    }

    // -------------------------------------------------------------------------
    // reactivate()
    // -------------------------------------------------------------------------

    public function testReactivateSuspendedCustomer(): void
    {
        $customer = new Customer();
        $customer->setStatus(CustomerStatus::SUSPENDED);

        $this->em->expects(self::once())->method('flush');

        $this->service->reactivate($customer);

        self::assertSame(CustomerStatus::ACTIVE, $customer->getStatus());
    }

    public function testReactivateActiveCustomerThrows(): void
    {
        $customer = new Customer();
        $customer->setStatus(CustomerStatus::ACTIVE);

        $this->expectException(DomainException::class);

        $this->service->reactivate($customer);
    }

    // -------------------------------------------------------------------------
    // close()
    // -------------------------------------------------------------------------

    /**
     * Нельзя закрыть клиента с неоплаченными счетами.
     * Проверяем что сервис обходит коллекцию и бросает исключение.
     */
    public function testCloseThrowsWhenHasPendingInvoices(): void
    {
        $customer = new Customer();
        $customer->setStatus(CustomerStatus::ACTIVE);

        // Добавляем счёт со статусом Pending
        $invoice = new \App\Entity\Invoice();
        $invoice->setStatus(\App\Enum\InvoiceStatus::PENDING);
        $invoice->setPeriod(new \DateTimeImmutable());
        $invoice->setDueDate(new \DateTimeImmutable('+30 days'));
        $invoice->setCustomer($customer);
        $invoice->recalculateTotal();

        // Добавляем счёт в коллекцию клиента через рефлексию
        $reflection = new \ReflectionProperty(Customer::class, 'invoices');
        $reflection->setAccessible(true);
        $collection = $reflection->getValue($customer);
        $collection->add($invoice);

        $this->expectException(DomainException::class);

        $this->service->close($customer);
    }
}