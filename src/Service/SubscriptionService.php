<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Subscription;
use App\Entity\TariffPlan;
use App\Exception\DomainException;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function assign(Customer $customer, TariffPlan $tariffPlan): Subscription
    {
        // Бизнес-правило: тариф должен быть активным
        if (! $tariffPlan->isActive()) {
            throw new DomainException("Тариф «{$tariffPlan->getName()}» недоступен для подключения.");
        }

        // Бизнес-правило: нет дублирующих активных подписок
        $existing = $this->subscriptionRepository->findActiveByCustomerAndTariff(
            $customer,
            $tariffPlan
        );
        if (null !== $existing) {
            throw new DomainException("Клиент уже подключён к тарифу «{$tariffPlan->getName()}».");
        }

        $subscription = new Subscription();
        $subscription->setCustomer($customer);
        $subscription->setTariffPlan($tariffPlan);
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setIsActive(true);

        $this->em->persist($subscription);
        $this->em->flush();

        return $subscription;
    }

    public function cancel(Subscription $subscription): void
    {
        if (! $subscription->isActive()) {
            throw new DomainException('Подписка уже отключена.');
        }

        $subscription->setIsActive(false);
        $subscription->setEndDate(new \DateTimeImmutable());

        $this->em->flush();
    }
}
