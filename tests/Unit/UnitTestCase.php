<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Базовый класс для всех Unit тестов.
 * Не использует БД, только моки.
 */
abstract class UnitTestCase extends TestCase
{
}