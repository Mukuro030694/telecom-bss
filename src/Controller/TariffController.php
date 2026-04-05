<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Tariff\CreateTariffDTO;
use App\DTO\Tariff\UpdateTariffDTO;
use App\Entity\TariffPlan;
use App\Exception\DomainException;
use App\Repository\TariffPlanRepository;
use App\Service\TariffService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tariffs', name: 'tariff_')]
class TariffController extends AbstractController
{
    public function __construct(
        private readonly TariffService       $tariffService,
        private readonly TariffPlanRepository $tariffPlanRepository,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('tariff/index.html.twig', [
            'tariffs' => $this->tariffPlanRepository->findAll(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('tariff/create.html.twig');
        }

        try {
            $dto = new CreateTariffDTO(
                name:         $request->request->getString('name'),
                description:  $request->request->get('description') !== null ? (string) $request->request->get('description') : null,
                monthlyPrice: $request->request->has('monthlyPrice')
                    ? (float) $request->request->get('monthlyPrice')
                    : 0.0,
                isActive:     $request->request->getBoolean('isActive', true),
            );

            $tariff = $this->tariffService->create($dto);

            $this->addFlash('success', "Тариф «{$tariff->getName()}» создан.");

            return $this->redirectToRoute('tariff_index');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('tariff/create.html.twig');
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function show(TariffPlan $tariffPlan): Response
    {
        return $this->render('tariff/show.html.twig', [
            'tariff' => $tariffPlan,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function edit(TariffPlan $tariffPlan, Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('tariff/edit.html.twig', [
                'tariff' => $tariffPlan,
            ]);
        }

        try {
            $dto = new UpdateTariffDTO(
                name:         $request->request->get('name') !== null ? (string) $request->request->get('name') : null,
                description:  $request->request->get('description') !== null ? (string) $request->request->get('description') : null,
                monthlyPrice: $request->request->has('monthlyPrice')
                    ? (float) $request->request->get('monthlyPrice')
                    : null,
                isActive:     $request->request->has('isActive')
                    ? $request->request->getBoolean('isActive')
                    : null,
            );

            $this->tariffService->update($tariffPlan, $dto);

            $this->addFlash('success', 'Тариф обновлён.');

            return $this->redirectToRoute('tariff_show', ['id' => $tariffPlan->getId()]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('tariff/edit.html.twig', ['tariff' => $tariffPlan]);
        }
    }

    #[Route('/{id}/deactivate', name: 'deactivate', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function deactivate(TariffPlan $tariffPlan): Response
    {
        try {
            $this->tariffService->deactivate($tariffPlan);
            $this->addFlash('warning', "Тариф «{$tariffPlan->getName()}» деактивирован.");
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('tariff_index');
    }
}