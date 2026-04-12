<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CustomerRepository;
use App\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly InvoiceRepository $invoiceRepository,
    ) {
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'totalCustomers' => $this->customerRepository->count([]),
            'pendingInvoices' => count($this->invoiceRepository->findByStatus(\App\Enum\InvoiceStatus::PENDING)),
            'overdueInvoices' => count($this->invoiceRepository->findByStatus(\App\Enum\InvoiceStatus::OVERDUE)),
        ]);
    }
}
