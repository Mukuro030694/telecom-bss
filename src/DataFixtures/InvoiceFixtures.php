<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Enum\InvoiceStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InvoiceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Генерируем счета за последние 3 месяца для активных клиентов
        // Индексы клиентов из CustomerFixtures (0,1,2,4,5,7 — активные)
        $activeCustomerIndexes = [0, 1, 2, 4, 5, 7];

        foreach ($activeCustomerIndexes as $customerIndex) {
            /** @var \App\Entity\Customer $customer */
            $customer = $this->getReference(
                CustomerFixtures::CUSTOMER_PREFIX . $customerIndex,
                \App\Entity\Customer::class
            );

            // Счета за 3 прошлых месяца
            for ($monthsAgo = 3; $monthsAgo >= 1; $monthsAgo--) {
                $period = (new \DateTimeImmutable())
                    ->modify("-{$monthsAgo} months")
                    ->modify('first day of this month')
                    ->setTime(0, 0, 0);

                $invoice = $this->buildInvoice($customer, $period, $monthsAgo);

                $manager->persist($invoice);
            }

            // Счёт за текущий месяц — у части клиентов pending, у части нет
            // Клиенты 0, 1, 4 уже получили счёт за этот месяц
            if (in_array($customerIndex, [0, 1, 4], true)) {
                $currentPeriod = (new \DateTimeImmutable())
                    ->modify('first day of this month')
                    ->setTime(0, 0, 0);

                $invoice = $this->buildInvoice($customer, $currentPeriod, 0);
                $manager->persist($invoice);
            }
        }

        // Один просроченный счёт — для проверки overdue в UI
        $overdueCustomer = $this->getReference(CustomerFixtures::CUSTOMER_PREFIX . '2', \App\Entity\Customer::class);
        $overduePeriod = (new \DateTimeImmutable())
            ->modify('-4 months')
            ->modify('first day of this month')
            ->setTime(0, 0, 0);

        $overdueInvoice = new Invoice();
        $overdueInvoice->setCustomer($overdueCustomer);
        $overdueInvoice->setPeriod($overduePeriod);
        $overdueInvoice->setStatus(InvoiceStatus::OVERDUE);
        $overdueInvoice->setDueDate($overduePeriod->modify('+30 days'));

        $item = new InvoiceItem();
        $item->setInvoice($overdueInvoice);
        $item->setDescription('Премиум — просроченный');
        $item->setAmount(99900);
        $overdueInvoice->addItem($item);
        $overdueInvoice->recalculateTotal();

        $manager->persist($overdueInvoice);
        $manager->flush();
    }

    private function buildInvoice(
        \App\Entity\Customer $customer,
        \DateTimeImmutable   $period,
        int                  $monthsAgo,
    ): Invoice {
        $invoice = new Invoice();
        $invoice->setCustomer($customer);
        $invoice->setPeriod($period);
        $invoice->setDueDate($period->modify('+30 days'));

        // Прошлые месяцы — оплачены, текущий — pending
        if ($monthsAgo > 0) {
            $invoice->setStatus(InvoiceStatus::PAID);
            $invoice->setPaidAt(
                $period->modify('+' . random_int(1, 25) . ' days')
            );
        } else {
            $invoice->setStatus(InvoiceStatus::PENDING);
        }

        // Добавляем позиции на основе активных подписок клиента
        foreach ($customer->getSubscriptions() as $subscription) {
            if (!$subscription->isActive()) {
                continue;
            }

            // Подписка должна была существовать в тот период
            if ($subscription->getStartDate() > $period) {
                continue;
            }

            $item = new InvoiceItem();
            $item->setInvoice($invoice);
            $item->setDescription(
                $subscription->getTariffPlan()->getName()
                . ' — ' . $period->format('m/Y')
            );
            $item->setAmount($subscription->getTariffPlan()->getMonthlyPrice());
            $invoice->addItem($item);
        }

        // Если подписок не было — добавляем заглушку
        // (в реальном проде такого не будет — сервис проверяет)
        if ($invoice->getItems()->isEmpty()) {
            $item = new InvoiceItem();
            $item->setInvoice($invoice);
            $item->setDescription('Базовая услуга');
            $item->setAmount(29900);
            $invoice->addItem($item);
        }

        $invoice->recalculateTotal();

        return $invoice;
    }

    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class,
            SubscriptionFixtures::class,
        ];
    }
}