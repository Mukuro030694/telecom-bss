<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\InvoiceGeneratedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: InvoiceGeneratedEvent::class, method: 'onInvoiceGenerated', priority: 10)]
final class InvoiceAuditLogListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onInvoiceGenerated(InvoiceGeneratedEvent $event): void
    {
        // priority: 10 — этот листенер выполняется РАНЬШЕ email-листенера (у него 0)
        // Аудит пишем первым, чтобы факт генерации был зафиксирован
        // даже если email-листенер потом упадёт
        $this->logger->info('Invoice generated', [
            'invoice_id' => (string) $event->getInvoice()->getId(),
            'customer_id' => (string) $event->getCustomer()->getId(),
            'period' => $event->getPeriodFormatted(),
            'total' => $event->getTotalFormatted(),
            'due_date' => $event->getInvoice()->getDueDate()->format('Y-m-d'),
        ]);
    }
}
