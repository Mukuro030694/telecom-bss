<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Subscription;
use App\Entity\TariffPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Для карточки клиента — его подписки с тарифами одним запросом.
     *
     * @return Subscription[]
     */
    public function findByCustomerWithTariff(Customer $customer): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.tariffPlan', 'tp')
            ->addSelect('tp')
            ->where('s.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Ключевая проверка в SubscriptionService::assign().
     * Клиент не может иметь две активные подписки на один тариф.
     */
    public function findActiveByCustomerAndTariff(
        Customer    $customer,
        TariffPlan  $tariffPlan,
    ): ?Subscription {
        return $this->createQueryBuilder('s')
            ->where('s.customer = :customer')
            ->andWhere('s.tariffPlan = :plan')
            ->andWhere('s.isActive = true')
            ->setParameter('customer', $customer)
            ->setParameter('plan', $tariffPlan)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Все активные подписки клиента — используется в BillingService
     * при генерации счёта, чтобы знать за что выставлять позиции.
     *
     * @return Subscription[]
     */
    public function findActiveByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.tariffPlan', 'tp')
            ->addSelect('tp')
            ->where('s.customer = :customer')
            ->andWhere('s.isActive = true')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult();
    }
}