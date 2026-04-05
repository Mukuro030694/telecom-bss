<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Subscription;
use App\Entity\TariffPlan;
use App\Exception\DomainException;
use App\Repository\TariffPlanRepository;
use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customers/{customerId}/subscriptions', name: 'subscription_')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionService  $subscriptionService,
        private readonly TariffPlanRepository $tariffPlanRepository,
    ) {}

    #[Route('/assign', name: 'assign', methods: ['GET', 'POST'])]
    public function assign(
        // ParamConverter по имени аргумента — customerId из роута
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(id: 'customerId')]
        Customer $customer,
        Request  $request,
    ): Response {
        $activeTariffs = $this->tariffPlanRepository->findAllActive();

        if (!$request->isMethod('POST')) {
            return $this->render('subscription/assign.html.twig', [
                'customer' => $customer,
                'tariffs'  => $activeTariffs,
            ]);
        }

        try {
            $tariffId = $request->request->getString('tariffPlanId');

            // Ищем тариф — если не найден, Symfony бросит 404
            $tariffPlan = $this->tariffPlanRepository->find($tariffId)
                ?? throw new DomainException('Тариф не найден.');

            $this->subscriptionService->assign($customer, $tariffPlan);

            $this->addFlash('success', "Тариф «{$tariffPlan->getName()}» подключён.");

            return $this->redirectToRoute('customer_show', ['id' => $customer->getId()]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('subscription/assign.html.twig', [
                'customer' => $customer,
                'tariffs'  => $activeTariffs,
            ]);
        }
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(id: 'customerId')]
        Customer     $customer,
        Subscription $subscription,
    ): Response {
        try {
            $this->subscriptionService->cancel($subscription);
            $this->addFlash('warning', 'Подписка отключена.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('customer_show', ['id' => $customer->getId()]);
    }
}