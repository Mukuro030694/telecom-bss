<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TariffPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TariffPlan>
 */
class TariffPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TariffPlan::class);
    }

    /**
     * Для select-а в форме подписки — только активные тарифы.
     *
     * @return TariffPlan[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('tp')
            ->where('tp.isActive = true')
            ->orderBy('tp.monthlyPrice', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Проверка перед деактивацией тарифа: есть ли активные подписки на него?
     * Используется в TariffService::deactivate().
     */
    public function hasActiveSubscriptions(TariffPlan $tariffPlan): bool
    {
        $count = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(\App\Entity\Subscription::class, 's')
            ->where('s.tariffPlan = :plan')
            ->andWhere('s.isActive = true')
            ->setParameter('plan', $tariffPlan)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
