<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Tariff\CreateTariffDTO;
use App\DTO\Tariff\UpdateTariffDTO;
use App\Entity\TariffPlan;
use App\Exception\DomainException;
use App\Repository\TariffPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TariffService
{
    public function __construct(
        private readonly TariffPlanRepository   $tariffPlanRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface     $validator,
    ) {}

    public function create(CreateTariffDTO $dto): TariffPlan
    {
        $this->validate($dto);

        $tariff = new TariffPlan();
        $tariff->setName($dto->name);
        $tariff->setDescription($dto->description);
        // Важно: храним в копейках — приходит float, сохраняем int
        $tariff->setMonthlyPrice((int) round($dto->monthlyPrice * 100));
        $tariff->setIsActive($dto->isActive);

        $this->em->persist($tariff);
        $this->em->flush();

        return $tariff;
    }

    public function update(TariffPlan $tariff, UpdateTariffDTO $dto): TariffPlan
    {
    $this->validate($dto);

    if ($dto->name !== null) {
        $tariff->setName($dto->name);
    }
    if ($dto->description !== null) {
        $tariff->setDescription($dto->description);
    }
    if ($dto->monthlyPrice !== null) {
        $tariff->setMonthlyPrice((int) round($dto->monthlyPrice * 100));
    }
    // Добавь это
    if ($dto->isActive !== null) {
        $tariff->setIsActive($dto->isActive);
    }

    $this->em->flush();

    return $tariff;
    }

    public function deactivate(TariffPlan $tariff): void
    {
        if (!$tariff->isActive()) {
            throw new DomainException('Тариф уже деактивирован.');
        }

        // Бизнес-правило: нельзя деактивировать тариф с активными подписками
        if ($this->tariffPlanRepository->hasActiveSubscriptions($tariff)) {
            throw new DomainException(
                "Нельзя деактивировать тариф «{$tariff->getName()}» — есть активные подписки."
            );
        }

        $tariff->setIsActive(false);
        $this->em->flush();
    }

    private function validate(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
    }
}