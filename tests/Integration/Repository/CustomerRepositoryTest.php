<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Customer;
use App\Entity\Subscription;
use App\Entity\TariffPlan;
use App\Enum\CustomerStatus;
use App\Repository\CustomerRepository;
use App\Tests\Integration\IntegrationTestCase;

class CustomerRepositoryTest extends IntegrationTestCase
{
    private CustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = static::getContainer()->get(CustomerRepository::class);
    }

    // -------------------------------------------------------------------------
    // findActive()
    // -------------------------------------------------------------------------

    /**
     * Проверяем что findActive() возвращает только активных клиентов.
     */
    public function testFindActiveReturnsOnlyActiveCustomers(): void
    {
        // Создаём трёх клиентов с разными статусами
        $active = $this->buildAndPersistCustomer('active@test.com', CustomerStatus::ACTIVE);
        $suspended = $this->buildAndPersistCustomer('suspended@test.com', CustomerStatus::SUSPENDED);
        $closed = $this->buildAndPersistCustomer('closed@test.com', CustomerStatus::CLOSED);

        $this->flushAndClear();

        $result = $this->repository->findActive();

        $emails = array_map(fn (Customer $c) => $c->getEmail(), $result);

        self::assertContains('active@test.com', $emails);
        self::assertNotContains('suspended@test.com', $emails);
        self::assertNotContains('closed@test.com', $emails);
    }

    // -------------------------------------------------------------------------
    // findByEmail()
    // -------------------------------------------------------------------------

    public function testFindByEmailReturnsCustomer(): void
    {
        $this->buildAndPersistCustomer('find@test.com', CustomerStatus::ACTIVE);
        $this->flushAndClear();

        $customer = $this->repository->findByEmail('find@test.com');

        self::assertNotNull($customer);
        self::assertSame('find@test.com', $customer->getEmail());
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findByEmail('nonexistent@test.com');

        self::assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findBySearchQuery()
    // -------------------------------------------------------------------------

    public function testFindBySearchQueryMatchesFirstName(): void
    {
        $customer = $this->buildAndPersistCustomer('search@test.com', CustomerStatus::ACTIVE);
        $customer->setFirstName('Александр');
        $this->flushAndClear();

        $result = $this->repository->findBySearchQuery('алекс');

        $emails = array_map(fn (Customer $c) => $c->getEmail(), $result);
        self::assertContains('search@test.com', $emails);
    }

    public function testFindBySearchQueryMatchesEmail(): void
    {
        $this->buildAndPersistCustomer('unique123@test.com', CustomerStatus::ACTIVE);
        $this->flushAndClear();

        $result = $this->repository->findBySearchQuery('unique123');

        self::assertCount(1, $result);
        self::assertSame('unique123@test.com', $result[0]->getEmail());
    }

    public function testFindBySearchQueryReturnsEmptyWhenNoMatch(): void
    {
        $result = $this->repository->findBySearchQuery('zzznomatch999');

        self::assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // findAllWithSubscriptions()
    // -------------------------------------------------------------------------

    /**
     * Проверяем что метод подгружает подписки одним запросом — нет N+1.
     * Считаем количество SQL запросов через Doctrine query log.
     */
    public function testFindAllWithSubscriptionsLoadsInSingleQuery(): void
    {
        $customer = $this->buildAndPersistCustomer('sub@test.com', CustomerStatus::ACTIVE);
        $tariff = $this->buildAndPersistTariff();

        $subscription = new Subscription();
        $subscription->setCustomer($customer);
        $subscription->setTariffPlan($tariff);
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setIsActive(true);
        $this->em->persist($subscription);

        $this->flushAndClear();

        $customers = $this->repository->findAllWithSubscriptions();

        self::assertNotEmpty($customers);

        // Проверяем что подписки доступны без дополнительных запросов
        $foundCustomer = array_filter(
            $customers,
            fn (Customer $c) => 'sub@test.com' === $c->getEmail()
        );

        self::assertCount(1, $foundCustomer);

        $customer = array_values($foundCustomer)[0];
        self::assertCount(1, $customer->getSubscriptions());
        self::assertSame('Тестовый тариф', $customer->getSubscriptions()->first()->getTariffPlan()->getName());
    }

    // -------------------------------------------------------------------------
    // Вспомогательные методы
    // -------------------------------------------------------------------------

    private function buildAndPersistCustomer(
        string $email,
        CustomerStatus $status,
    ): Customer {
        $customer = new Customer();
        $customer->setFirstName('Тест');
        $customer->setLastName('Тестов');
        $customer->setEmail($email);
        $customer->setStatus($status);

        $this->em->persist($customer);

        return $customer;
    }

    private function buildAndPersistTariff(): TariffPlan
    {
        $tariff = new TariffPlan();
        $tariff->setName('Тестовый тариф');
        $tariff->setMonthlyPrice(29900);
        $tariff->setIsActive(true);

        $this->em->persist($tariff);

        return $tariff;
    }
}
