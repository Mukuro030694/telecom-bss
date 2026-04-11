<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Enum\InvoiceStatus;
use App\Event\InvoiceGeneratedEvent;
use App\Exception\DomainException;
use App\Repository\InvoiceRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BillingService
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Генерация счёта за месяц.
     * $period — любая дата внутри нужного месяца, нормализуем до первого числа.
     */
    public function generateMonthlyInvoice(
        Customer $customer,
        \DateTimeImmutable $period,
    ): Invoice {
        // Нормализуем период до первого числа месяца
        $normalizedPeriod = new \DateTimeImmutable(
            $period->format('Y-m-01 00:00:00')
        );

        // Бизнес-правило: один счёт на один период
        $existing = $this->invoiceRepository->findByCustomerAndPeriod(
            $customer,
            $normalizedPeriod
        );
        if (null !== $existing) {
            throw new DomainException("Счёт за {$normalizedPeriod->format('m/Y')} уже существует.");
        }

        // Получаем активные подписки — это позиции счёта
        $subscriptions = $this->subscriptionRepository->findActiveByCustomer($customer);

        if (empty($subscriptions)) {
            throw new DomainException('Нельзя выставить счёт: y клиента нет активных подписок.');
        }

        $invoice = new Invoice();
        $invoice->setCustomer($customer);
        $invoice->setPeriod($normalizedPeriod);
        $invoice->setStatus(InvoiceStatus::PENDING);
        $invoice->setDueDate($normalizedPeriod->modify('first day of next month'));

        // Создаём позицию за каждую подписку
        foreach ($subscriptions as $subscription) {
            $item = new InvoiceItem();
            $item->setInvoice($invoice);
            $item->setDescription($subscription->getTariffPlan()->getName());
            $item->setAmount($subscription->getTariffPlan()->getMonthlyPrice());
            $invoice->addItem($item);
        }

        // Сумма считается в методе Invoice — сервис только вызывает
        $invoice->recalculateTotal();

        $this->em->persist($invoice);
        $this->em->flush();

        // Уведомление — через событие, не через прямой вызов
        $this->eventDispatcher->dispatch(new InvoiceGeneratedEvent($invoice));

        return $invoice;
    }

    public function markAsPaid(Invoice $invoice): void
    {
        if (InvoiceStatus::PENDING !== $invoice->getStatus()) {
            throw new DomainException("Нельзя оплатить счёт co статусом: {$invoice->getStatus()->label()}");
        }

        $invoice->setStatus(InvoiceStatus::PAID);
        $invoice->setPaidAt(new \DateTimeImmutable());

        $this->em->flush();
    }

    public function markAsOverdue(Invoice $invoice): void
    {
        if (InvoiceStatus::PENDING !== $invoice->getStatus()) {
            return; // идемпотентно — уже не pending, просто игнорируем
        }

        if ($invoice->getDueDate() > new \DateTimeImmutable()) {
            throw new DomainException('Срок оплаты ещё не истёк.');
        }

        $invoice->setStatus(InvoiceStatus::OVERDUE);
        $this->em->flush();
    }

    /**
     * Пакетная отметка просроченных счетов — для Symfony Console Command
     * или Scheduler. Вызывается фоновой задачей раз в день.
     */
    public function processOverdueInvoices(): int
    {
        $overdueInvoices = $this->invoiceRepository->findOverdue();
        $processed = 0;

        foreach ($overdueInvoices as $invoice) {
            $invoice->setStatus(InvoiceStatus::OVERDUE);
            ++$processed;
        }

        // flush один раз для всего пакета — не N раз в цикле
        $this->em->flush();

        return $processed;
    }
}
