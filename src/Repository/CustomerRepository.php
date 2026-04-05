<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Enum\CustomerStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * Для главной страницы: все клиенты с подгрузкой подписок и тарифов.
     * Один запрос вместо N+1 благодаря JOIN.
     *
     * @return Customer[]
     */
    public function findAllWithSubscriptions(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.subscriptions', 's')
            ->leftJoin('s.tariffPlan', 'tp')
            ->addSelect('s', 'tp')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Только активные — например для генерации счетов.
     *
     * @return Customer[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', CustomerStatus::ACTIVE)
            ->orderBy('c.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Поиск по имени, фамилии или email — для строки поиска в UI.
     *
     * @return Customer[]
     */
    public function findBySearchQuery(string $query): array
    {
        $term = '%'.mb_strtolower(trim($query)).'%';

        return $this->createQueryBuilder('c')
            ->where('LOWER(c.firstName) LIKE :term')
            ->orWhere('LOWER(c.lastName) LIKE :term')
            ->orWhere('LOWER(c.email) LIKE :term')
            ->setParameter('term', $term)
            ->orderBy('c.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Используется в сервисе при создании клиента — проверить уникальность email.
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Используется в контроллере/сервисе когда клиент обязан существовать.
     * Бросает доменное исключение, а не Doctrine-исключение.
     */
    public function getOrFail(string $id): Customer
    {
        $customer = $this->find($id);

        if (null === $customer) {
            throw new \App\Exception\CustomerNotFoundException("Customer with id {$id} not found.");
        }

        return $customer;
    }
}
