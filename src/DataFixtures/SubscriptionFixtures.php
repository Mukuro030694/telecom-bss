<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Subscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubscriptionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Каждый элемент: [индекс клиента, ссылка на тариф, активна?, сдвиг старта в месяцах]
        $assignments = [
            [0, TariffPlanFixtures::TARIFF_STANDARD, true,  -3],
            [1, TariffPlanFixtures::TARIFF_BASIC,    true,  -6],
            [1, TariffPlanFixtures::TARIFF_PREMIUM,  true,  -1], // у Петровой два тарифа
            [2, TariffPlanFixtures::TARIFF_PREMIUM,  true,  -2],
            [3, TariffPlanFixtures::TARIFF_BASIC,    true,  -5], // suspended клиент
            [4, TariffPlanFixtures::TARIFF_BUSINESS, true,  -4],
            [5, TariffPlanFixtures::TARIFF_STANDARD, true,  -1],
            [5, TariffPlanFixtures::TARIFF_BASIC,    false, -8], // отменённая подписка
            [6, TariffPlanFixtures::TARIFF_BASIC,    false, -12], // закрытый клиент
            [7, TariffPlanFixtures::TARIFF_PREMIUM,  true,  -2],
        ];

        foreach ($assignments as [$customerIndex, $tariffRef, $isActive, $monthsAgo]) {
            $customer = $this->getReference(
                CustomerFixtures::CUSTOMER_PREFIX . $customerIndex,
                \App\Entity\Customer::class
            );
            $tariff = $this->getReference($tariffRef, \App\Entity\TariffPlan::class);

            $startDate = (new \DateTimeImmutable())
                ->modify("{$monthsAgo} months")
                ->modify('first day of this month');

            $subscription = new Subscription();
            $subscription->setCustomer($customer);
            $subscription->setTariffPlan($tariff);
            $subscription->setStartDate($startDate);
            $subscription->setIsActive($isActive);

            // Если отменена — ставим дату окончания
            if (!$isActive) {
                $subscription->setEndDate(new \DateTimeImmutable('last month'));
            }

            $manager->persist($subscription);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class,
            TariffPlanFixtures::class,
        ];
    }
}