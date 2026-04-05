<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Exception\DomainException;
use App\Repository\InvoiceRepository;
use App\Service\BillingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices', name: 'invoice_')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly BillingService   $billingService,
        private readonly InvoiceRepository $invoiceRepository,
    ) {}

    // Все счета — для дашборда или страницы биллинга
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('invoice/index.html.twig', [
            'pending'  => $this->invoiceRepository->findByStatus(\App\Enum\InvoiceStatus::PENDING),
            'overdue'  => $this->invoiceRepository->findByStatus(\App\Enum\InvoiceStatus::OVERDUE),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Invoice $invoice): Response
    {
        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    // Генерация счёта — форма выбора периода для конкретного клиента
    #[Route('/generate/{customerId}', name: 'generate', methods: ['GET', 'POST'])]
    public function generate(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(id: 'customerId')]
        Customer $customer,
        Request  $request,
    ): Response {
        if (!$request->isMethod('POST')) {
            return $this->render('invoice/generate.html.twig', [
                'customer' => $customer,
            ]);
        }

        try {
            // Из формы приходит строка "2024-03" — парсим в дату
            $periodString = $request->request->getString('period');
            $period = new \DateTimeImmutable($periodString . '-01');

            $invoice = $this->billingService->generateMonthlyInvoice($customer, $period);

            $this->addFlash('success', "Счёт за {$period->format('m/Y')} сформирован.");

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('invoice/generate.html.twig', ['customer' => $customer]);
        }
    }

    #[Route('/{id}/pay', name: 'pay', methods: ['POST'])]
    public function pay(Invoice $invoice): Response
    {
        try {
            $this->billingService->markAsPaid($invoice);
            $this->addFlash('success', 'Счёт отмечен как оплаченный.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
    }
}