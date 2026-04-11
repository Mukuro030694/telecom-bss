<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Начинаем транзакцию — всё что делаем в тесте будет откачено
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Откатываем транзакцию — БД остаётся чистой для следующего теста
        $this->em->getConnection()->rollBack();

        parent::tearDown();
    }

    /**
     * Сбрасываем изменения в БД внутри теста.
     * Нужно чтобы проверить данные которые только что сохранили.
     */
    protected function flushAndClear(): void
    {
        $this->em->flush();
        $this->em->clear();
    }
}
