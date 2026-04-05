<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Customer\CreateCustomerDTO;
use App\DTO\Customer\UpdateCustomerDTO;
use App\Entity\Customer;
use App\Exception\DomainException;
use App\Repository\CustomerRepository;
use App\Service\CustomerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customers', name: 'customer_')]
class CustomerController extends AbstractController
{
    public function __construct(
        private readonly CustomerService    $customerService,
        private readonly CustomerRepository $customerRepository,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $request->query->getString('q');

        $customers = $query
            ? $this->customerRepository->findBySearchQuery($query)
            : $this->customerRepository->findAllWithSubscriptions();

        return $this->render('customer/index.html.twig', [
            'customers' => $customers,
            'query'     => $query,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('customer/create.html.twig');
        }

        try {
            $dto = new CreateCustomerDTO(
                firstName: $request->request->getString('firstName'),
                lastName:  $request->request->getString('lastName'),
                email:     $request->request->getString('email'),
                phone:     $request->request->getString('phone', '') ?: null,
            );

            $customer = $this->customerService->create($dto);

            $this->addFlash('success', "Клиент «{$customer->getFullName()}» создан.");

            return $this->redirectToRoute('customer_show', [
                'id' => $customer->getId(),
            ]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('customer/create.html.twig');
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function edit(Customer $customer, Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('customer/edit.html.twig', [
                'customer' => $customer,
            ]);
        }

        try {
            $dto = new UpdateCustomerDTO(
                firstName: $request->request->getString('firstName', '') ?: null,
                lastName:  $request->request->getString('lastName', '') ?: null,
                email:     $request->request->getString('email', '') ?: null,
                phone:     $request->request->getString('phone', '') ?: null,
            );

            $this->customerService->update($customer, $dto);

            $this->addFlash('success', 'Данные клиента обновлены.');

            return $this->redirectToRoute('customer_show', ['id' => $customer->getId()]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('customer/edit.html.twig', ['customer' => $customer]);
        }
    }

    #[Route('/{id}/suspend', name: 'suspend', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function suspend(Customer $customer): Response
    {
        try {
            $this->customerService->suspend($customer);
            $this->addFlash('warning', "Аккаунт «{$customer->getFullName()}» приостановлен.");
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('customer_show', ['id' => $customer->getId()]);
    }

    #[Route('/{id}/reactivate', name: 'reactivate', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function reactivate(Customer $customer): Response
    {
        try {
            $this->customerService->reactivate($customer);
            $this->addFlash('success', "Аккаунт «{$customer->getFullName()}» реактивирован.");
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('customer_show', ['id' => $customer->getId()]);
    }

    #[Route('/{id}/close', name: 'close', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function close(Customer $customer): Response
    {
        try {
            $this->customerService->close($customer);
            $this->addFlash('info', "Аккаунт «{$customer->getFullName()}» закрыт.");
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('customer_show', ['id' => $customer->getId()]);
    }
}