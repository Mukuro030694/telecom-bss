<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Invoice;

/**
 * Dispatched когда счёт успешно создан и сохранён в БД.
 * Listeners используют это событие для уведомлений, логирования и т.д.
 */
final class InvoiceGeneratedEvent
{
    public function __construct(
        private readonly Invoice $invoice,
    ) {}

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    // Удобные shortcut-методы чтобы не писать getInvoice()->getCustomer() везде
    public function getCustomer(): \App\Entity\Customer
    {
        return $this->invoice->getCustomer();
    }

    public function getPeriodFormatted(): string
    {
        return $this->invoice->getPeriod()->format('m/Y');
    }

    public function getTotalFormatted(): string
    {
        // Конвертируем копейки в рубли для отображения
        return number_format($this->invoice->getTotalAmount() / 100, 2, '.', ' ') . ' ₽';
    }
}