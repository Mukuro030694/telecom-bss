<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Enum\InvoiceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Все счета клиента с позициями — для страницы клиента.
     *
     * @return Invoice[]
     */
    public function findByCustomerWithItems(Customer $customer): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.items', 'item')
            ->addSelect('item')
            ->where('i.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('i.period', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Ключевая проверка в BillingService::generateMonthlyInvoice().
     * Гарантирует что за один период не будет двух счетов.
     */
    public function findByCustomerAndPeriod(
        Customer $customer,
        \DateTimeImmutable $period,
    ): ?Invoice {
        return $this->createQueryBuilder('i')
            ->where('i.customer = :customer')
            ->andWhere('i.period = :period')
            ->setParameter('customer', $customer)
            ->setParameter('period', $period)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Просроченные счета — для фоновой задачи или дашборда.
     * Pending и срок оплаты прошёл.
     *
     * @return Invoice[]
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('i.dueDate < :now')
            ->setParameter('status', InvoiceStatus::PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Сумма всех оплаченных счетов — для дашборда/статистики.
     * Возвращает int (копейки) или 0 если нет оплат.
     */
    public function sumPaidAmount(): int
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalAmount)')
            ->where('i.status = :status')
            ->setParameter('status', InvoiceStatus::PAID)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Для страницы всех счетов с фильтром по статусу.
     *
     * @return Invoice[]
     */
    public function findByStatus(InvoiceStatus $status): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.customer', 'c')
            ->addSelect('c')
            ->where('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
