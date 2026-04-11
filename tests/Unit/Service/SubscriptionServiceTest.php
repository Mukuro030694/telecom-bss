<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Customer;
use App\Entity\Subscription;
use App\Entity\TariffPlan;
use App\Enum\CustomerStatus;
use App\Exception\DomainException;
use App\Repository\SubscriptionRepository;
use App\Service\SubscriptionService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionServiceTest extends UnitTestCase
{
    private SubscriptionRepository&\PHPUnit\Framework\MockObject\MockObject $subscriptionRepository;
    private EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject $em;
    private SubscriptionService $service;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new SubscriptionService(
            $this->subscriptionRepository,
            $this->em,
        );
    }

    // -------------------------------------------------------------------------
    // assign()
    // -------------------------------------------------------------------------

    public function testAssignSuccessfully(): void
    {
        $customer = $this->buildCustomer();
        $tariff = $this->buildTariff(active: true);

        // Нет существующей подписки
        $this->subscriptionRepository
            ->method('findActiveByCustomerAndTariff')
            ->willReturn(null);

        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $subscription = $this->service->assign($customer, $tariff);

        self::assertTrue($subscription->isActive());
        self::assertSame($customer, $subscription->getCustomer());
        self::assertSame($tariff, $subscription->getTariffPlan());
        self::assertInstanceOf(\DateTimeImmutable::class, $subscription->getStartDate());
    }

    /**
     * Нельзя подключить неактивный тариф.
     */
    public function testAssignThrowsWhenTariffInactive(): void
    {
        $customer = $this->buildCustomer();
        $tariff = $this->buildTariff(active: false);

        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $this->expectException(DomainException::class);

        $this->service->assign($customer, $tariff);
    }

    /**
     * Нельзя подключить тариф который уже подключён.
     */
    public function testAssignThrowsWhenAlreadySubscribed(): void
    {
        $customer = $this->buildCustomer();
        $tariff = $this->buildTariff(active: true);

        // Уже есть активная подписка на этот тариф
        $this->subscriptionRepository
            ->method('findActiveByCustomerAndTariff')
            ->willReturn(new Subscription());

        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $this->expectException(DomainException::class);

        $this->service->assign($customer, $tariff);
    }

    // -------------------------------------------------------------------------
    // cancel()
    // -------------------------------------------------------------------------

    public function testCancelSuccessfully(): void
    {
        $subscription = $this->buildSubscription(active: true);

        $this->em->expects(self::once())->method('flush');

        $this->service->cancel($subscription);

        self::assertFalse($subscription->isActive());
        self::assertInstanceOf(\DateTimeImmutable::class, $subscription->getEndDate());
    }

    /**
     * Нельзя отменить уже отменённую подписку.
     */
    public function testCancelThrowsWhenAlreadyInactive(): void
    {
        $subscription = $this->buildSubscription(active: false);

        $this->em->expects(self::never())->method('flush');

        $this->expectException(DomainException::class);

        $this->service->cancel($subscription);
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

    private function buildTariff(bool $active): TariffPlan
    {
        $tariff = new TariffPlan();
        $tariff->setName('Тестовый тариф');
        $tariff->setMonthlyPrice(29900);
        $tariff->setIsActive($active);

        return $tariff;
    }

    private function buildSubscription(bool $active): Subscription
    {
        $subscription = new Subscription();
        $subscription->setCustomer($this->buildCustomer());
        $subscription->setTariffPlan($this->buildTariff(active: true));
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setIsActive($active);

        return $subscription;
    }
}
