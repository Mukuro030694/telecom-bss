<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\InvoiceGeneratedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsEventListener(event: InvoiceGeneratedEvent::class, method: 'onInvoiceGenerated')]
final class InvoiceEmailNotificationListener
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onInvoiceGenerated(InvoiceGeneratedEvent $event): void
    {
        $customer = $event->getCustomer();

        try {
            $email = (new Email())
                ->from('billing@telecom-bss.local')
                ->to($customer->getEmail())
                ->subject("Счёт за {$event->getPeriodFormatted()} сформирован")
                ->html($this->buildEmailBody($event));

            $this->mailer->send($email);

            $this->logger->info('Invoice email sent', [
                'customer_id' => (string) $customer->getId(),
                'invoice_id' => (string) $event->getInvoice()->getId(),
            ]);
        } catch (\Throwable $e) {
            // Важно: НЕ пробрасываем исключение дальше.
            // Сбой уведомления не должен откатить транзакцию со счётом.
            $this->logger->error('Failed to send invoice email', [
                'customer_id' => (string) $customer->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildEmailBody(InvoiceGeneratedEvent $event): string
    {
        $customer = $event->getCustomer();

        return sprintf(
            '<p>Уважаемый %s,</p>
             <p>Сформирован счёт за <strong>%s</strong> на сумму <strong>%s</strong>.</p>
             <p>Срок оплаты: %s</p>',
            htmlspecialchars($customer->getFullName()),
            $event->getPeriodFormatted(),
            $event->getTotalFormatted(),
            $event->getInvoice()->getDueDate()->format('d.m.Y'),
        );
    }
}
