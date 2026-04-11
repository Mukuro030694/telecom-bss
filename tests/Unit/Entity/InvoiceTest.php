<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Tests\Unit\UnitTestCase;

class InvoiceTest extends UnitTestCase
{
    /**
     * Проверяем что recalculateTotal() правильно суммирует позиции.
     * Это чистая логика без зависимостей — идеально для Unit теста.
     */
    public function testRecalculateTotalSumsAllItems(): void
    {
        $invoice = new Invoice();

        $item1 = new InvoiceItem();
        $item1->setDescription('Базовый тариф');
        $item1->setAmount(29900);

        $item2 = new InvoiceItem();
        $item2->setDescription('Премиум тариф');
        $item2->setAmount(99900);

        $invoice->addItem($item1);
        $invoice->addItem($item2);
        $invoice->recalculateTotal();

        // 29900 + 99900 = 129800
        self::assertSame(129800, $invoice->getTotalAmount());
    }

    /**
     * Проверяем что счёт без позиций имеет нулевую сумму.
     */
    public function testRecalculateTotalWithNoItemsIsZero(): void
    {
        $invoice = new Invoice();
        $invoice->recalculateTotal();

        self::assertSame(0, $invoice->getTotalAmount());
    }

    /**
     * Проверяем что addItem не добавляет одну и ту же позицию дважды.
     */
    public function testAddItemDoesNotDuplicate(): void
    {
        $invoice = new Invoice();

        $item = new InvoiceItem();
        $item->setDescription('Тариф');
        $item->setAmount(29900);

        // Добавляем один и тот же объект дважды
        $invoice->addItem($item);
        $invoice->addItem($item);

        self::assertCount(1, $invoice->getItems());
    }

    /**
     * Проверяем что новый счёт имеет статус по умолчанию.
     */
    public function testNewInvoiceHasCreatedAt(): void
    {
        $invoice = new Invoice();

        self::assertInstanceOf(\DateTimeImmutable::class, $invoice->getCreatedAt());
    }
}
