<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Enum\CustomerStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture implements DependentFixtureInterface
{
    // Префикс для ссылок — будем генерировать customer-0, customer-1 ...
    public const CUSTOMER_PREFIX = 'customer-';

    /** @var array<int, array<string, mixed>> */
    private array $customers = [
        [
            'firstName' => 'Алексей',
            'lastName'  => 'Иванов',
            'email'     => 'ivanov@example.com',
            'phone'     => '+7 900 000 01 01',
            'status'    => CustomerStatus::ACTIVE,
        ],
        [
            'firstName' => 'Мария',
            'lastName'  => 'Петрова',
            'email'     => 'petrova@example.com',
            'phone'     => '+7 900 000 01 02',
            'status'    => CustomerStatus::ACTIVE,
        ],
        [
            'firstName' => 'Дмитрий',
            'lastName'  => 'Сидоров',
            'email'     => 'sidorov@example.com',
            'phone'     => null,
            'status'    => CustomerStatus::ACTIVE,
        ],
        [
            'firstName' => 'Елена',
            'lastName'  => 'Козлова',
            'email'     => 'kozlova@example.com',
            'phone'     => '+7 900 000 01 04',
            'status'    => CustomerStatus::SUSPENDED, // для проверки UI suspended
        ],
        [
            'firstName' => 'Сергей',
            'lastName'  => 'Новиков',
            'email'     => 'novikov@example.com',
            'phone'     => '+7 900 000 01 05',
            'status'    => CustomerStatus::ACTIVE,
        ],
        [
            'firstName' => 'Анна',
            'lastName'  => 'Морозова',
            'email'     => 'morozova@example.com',
            'phone'     => '+7 900 000 01 06',
            'status'    => CustomerStatus::ACTIVE,
        ],
        [
            'firstName' => 'Игорь',
            'lastName'  => 'Волков',
            'email'     => 'volkov@example.com',
            'phone'     => null,
            'status'    => CustomerStatus::CLOSED, // закрытый — для проверки UI
        ],
        [
            'firstName' => 'Ольга',
            'lastName'  => 'Лебедева',
            'email'     => 'lebedeva@example.com',
            'phone'     => '+7 900 000 01 08',
            'status'    => CustomerStatus::ACTIVE,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->customers as $index => $data) {
            $customer = new Customer();
            $customer->setFirstName($data['firstName']);
            $customer->setLastName($data['lastName']);
            $customer->setEmail($data['email']);
            $customer->setPhone($data['phone']);
            $customer->setStatus($data['status']);

            $manager->persist($customer);

            $this->addReference(self::CUSTOMER_PREFIX . $index, $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        // CustomerFixtures не зависит от других, но явно объявляем
        // что TariffPlanFixtures должны быть загружены первыми
        return [
            TariffPlanFixtures::class,
        ];
    }
}