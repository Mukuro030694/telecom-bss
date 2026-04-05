<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\TariffPlan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TariffPlanFixtures extends Fixture
{
    // Константы для ссылок — строки, по которым другие фикстуры
    // получат эти объекты через getReference()
    public const TARIFF_BASIC    = 'tariff-basic';
    public const TARIFF_STANDARD = 'tariff-standard';
    public const TARIFF_PREMIUM  = 'tariff-premium';
    public const TARIFF_BUSINESS = 'tariff-business';
    public const TARIFF_ARCHIVE  = 'tariff-archive';

    /** @var array<int, array<string, mixed>> */
    private array $tariffs = [
        [
            'ref'         => self::TARIFF_BASIC,
            'name'        => 'Базовый',
            'description' => 'Интернет 50 Мбит/c, 100 минут звонков',
            'price'       => 29900, // копейки = 299.00 ₽
            'isActive'    => true,
        ],
        [
            'ref'         => self::TARIFF_STANDARD,
            'name'        => 'Стандарт',
            'description' => 'Интернет 100 Мбит/c, безлимитные звонки',
            'price'       => 59900,
            'isActive'    => true,
        ],
        [
            'ref'         => self::TARIFF_PREMIUM,
            'name'        => 'Премиум',
            'description' => 'Интернет 500 Мбит/c, безлимитные звонки, ТВ',
            'price'       => 99900,
            'isActive'    => true,
        ],
        [
            'ref'         => self::TARIFF_BUSINESS,
            'name'        => 'Бизнес',
            'description' => 'Выделенный канал 1 Гбит/c, статический IP',
            'price'       => 199900,
            'isActive'    => true,
        ],
        [
            'ref'         => self::TARIFF_ARCHIVE,
            'name'        => 'Архивный 2022',
            'description' => 'Снят c продажи',
            'price'       => 19900,
            'isActive'    => false, // неактивный — для проверки что не показывается
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->tariffs as $data) {
            $tariff = new TariffPlan();
            $tariff->setName($data['name']);
            $tariff->setDescription($data['description']);
            $tariff->setMonthlyPrice($data['price']);
            $tariff->setIsActive($data['isActive']);

            $manager->persist($tariff);

            // Сохраняем ссылку — другие фикстуры получат объект по ключу
            $this->addReference($data['ref'], $tariff);
        }

        $manager->flush();
    }
}