<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Subscription;
use App\Entity\TariffPlan;
use App\Enum\CustomerStatus;
use App\Enum\InvoiceStatus;
use App\Exception\DomainException;
use App\Repository\InvoiceRepository;
use App\Repository\SubscriptionRepository;
use App\Service\BillingService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BillingServiceTest extends UnitTestCase
{
    private InvoiceRepository&\PHPUnit\Framework\MockObject\MockObject $invoiceRepository;
    private SubscriptionRepository&\PHPUnit\Framework\MockObject\MockObject $subscriptionRepository;
    private EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject $em;
    private EventDispatcherInterface&\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;
    private BillingService $service;

    protected function setUp(): void
    {
        $this->invoiceRepository = $this->createMock(InvoiceRepository::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new BillingService(
            $this->invoiceRepository,
            $this->subscriptionRepository,
            $this->em,
            $this->eventDispatcher,
        );
    }

    // -------------------------------------------------------------------------
    // generateMonthlyInvoice()
    // -------------------------------------------------------------------------

    public function testGenerateMonthlyInvoiceSuccessfully(): void
    {
        $customer = $this->buildCustomer();
        $period = new \DateTimeImmutable('2024-03-15');

        $this->invoiceRepository
            ->method('findByCustomerAndPeriod')
            ->willReturn(null);

        $this->subscriptionRepository
            ->method('findActiveByCustomer')
            ->willReturn([
                $this->buildSubscription(29900),
                $this->buildSubscription(59900),
            ]);

        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');
        $this->eventDispatcher->expects(self::once())->method('dispatch');

        $invoice = $this->service->generateMonthlyInvoice($customer, $period);

        self::assertSame('2024-03-01', $invoice->getPeriod()->format('Y-m-d'));
        self::assertSame(89800, $invoice->getTotalAmount());
        self::assertSame(InvoiceStatus::PENDING, $invoice->getStatus());
        self::assertSame('2024-04-01', $invoice->getDueDate()->format('Y-m-d'));
    }

    public function testGenerateThrowsWhenInvoiceAlreadyExists(): void
    {
        $customer = $this->buildCustomer();
        $period = new \DateTimeImmutable('2024-03-01');

        $this->invoiceRepository
            ->method('findByCustomerAndPeriod')
            ->willReturn(new Invoice());

        $this->em->expects(self::never())->method('flush');
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->expectException(DomainException::class);

        $this->service->generateMonthlyInvoice($customer, $period);
    }

    public function testGenerateThrowsWhenNoActiveSubscriptions(): void
    {
        $customer = $this->buildCustomer();
        $period = new \DateTimeImmutable('2024-03-01');

        $this->invoiceRepository
            ->method('findByCustomerAndPeriod')
            ->willReturn(null);

        $this->subscriptionRepository
            ->method('findActiveByCustomer')
            ->willReturn([]);

        $this->em->expects(self::never())->method('flush');

        $this->expectException(DomainException::class);

        $this->service->generateMonthlyInvoice($customer, $period);
    }

    public function testPeriodIsNormalizedToFirstDayOfMonth(): void
    {
        $customer = $this->buildCustomer();

        $this->invoiceRepository
            ->method('findByCustomerAndPeriod')
            ->willReturn(null);

        $this->subscriptionRepository
            ->method('findActiveByCustomer')
            ->willReturn([$this->buildSubscription(29900)]);

        $invoice = $this->service->generateMonthlyInvoice(
            $customer,
            new \DateTimeImmutable('2024-06-15'),
        );

        self::assertSame('2024-06-01', $invoice->getPeriod()->format('Y-m-d'));
    }

    // -------------------------------------------------------------------------
    // markAsPaid()
    // -------------------------------------------------------------------------

    public function testMarkAsPaidSuccessfully(): void
    {
        $invoice = $this->buildInvoice(InvoiceStatus::PENDING);

        $this->em->expects(self::once())->method('flush');

        $this->service->markAsPaid($invoice);

        self::assertSame(InvoiceStatus::PAID, $invoice->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $invoice->getPaidAt());
    }

    public function testMarkAsPaidThrowsWhenAlreadyPaid(): void
    {
        $invoice = $this->buildInvoice(InvoiceStatus::PAID);

        $this->em->expects(self::never())->method('flush');

        $this->expectException(DomainException::class);

        $this->service->markAsPaid($invoice);
    }

    public function testMarkAsPaidThrowsWhenOverdue(): void
    {
        $invoice = $this->buildInvoice(InvoiceStatus::OVERDUE);

        $this->expectException(DomainException::class);

        $this->service->markAsPaid($invoice);
    }

    // -------------------------------------------------------------------------
    // processOverdueInvoices()
    // -------------------------------------------------------------------------

    public function testProcessOverdueInvoicesFlushesOnce(): void
    {
        $invoice1 = $this->buildInvoice(InvoiceStatus::PENDING);
        $invoice2 = $this->buildInvoice(InvoiceStatus::PENDING);

        $this->invoiceRepository
            ->method('findOverdue')
            ->willReturn([$invoice1, $invoice2]);

        $this->em->expects(self::once())->method('flush');

        $processed = $this->service->processOverdueInvoices();

        self::assertSame(2, $processed);
        self::assertSame(InvoiceStatus::OVERDUE, $invoice1->getStatus());
        self::assertSame(InvoiceStatus::OVERDUE, $invoice2->getStatus());
    }

    // -------------------------------------------------------------------------
    // Вспомогательные методы
    // -------------------------------------------------------------------------

    private function buildCustomer(): Customer
    {
        $customer = new Customer();
        $customer->setFirstName('Тест');
        $customer->setLastName('Тестов');
        $customer->setEmail('test@example.com');
        $customer->setStatus(CustomerStatus::ACTIVE);

        return $customer;
    }

    private function buildSubscription(int $price): Subscription
    {
        $tariff = new TariffPlan();
        $tariff->setName('Тестовый тариф');
        $tariff->setMonthlyPrice($price);
        $tariff->setIsActive(true);

        $subscription = new Subscription();
        $subscription->setTariffPlan($tariff);
        $subscription->setCustomer($this->buildCustomer());
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setIsActive(true);

        return $subscription;
    }

    private function buildInvoice(InvoiceStatus $status): Invoice
    {
        $invoice = new Invoice();
        $invoice->setStatus($status);
        $invoice->setPeriod(new \DateTimeImmutable());
        $invoice->setDueDate(new \DateTimeImmutable('+30 days'));
        $invoice->setCustomer($this->buildCustomer());
        $invoice->recalculateTotal();

        return $invoice;
    }
}
