<?php

namespace App\Controller;

use App\Repository\InvoiceRepository;
use App\Service\InvoiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/invoice')]
#[IsGranted('ROLE_USER')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private InvoiceService $invoiceService,
    ) {
    }

    #[Route('/{id}', name: 'invoice_view')]
    public function view(int $id): Response
    {
        $invoice = $this->invoiceRepository->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }

        // Check permissions - user must be part of the team
        $subscription = $invoice->getPayment()->getSubscription();
        $this->denyAccessUnlessGranted('SUBSCRIPTION_VIEW', $subscription);

        return $this->render('invoice/view.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/download', name: 'invoice_download')]
    public function download(int $id): Response
    {
        $invoice = $this->invoiceRepository->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }

        // Check permissions
        $subscription = $invoice->getPayment()->getSubscription();
        $this->denyAccessUnlessGranted('SUBSCRIPTION_VIEW', $subscription);

        $pdfPath = $invoice->getPdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            // Regenerate PDF if missing
            $pdfPath = $this->invoiceService->generatePDF($invoice);
        }

        $response = new BinaryFileResponse($pdfPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('invoice-%s.pdf', $invoice->getInvoiceNumber())
        );

        return $response;
    }

    #[Route('s', name: 'invoice_list')]
    public function list(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Get user's teams
        $teams = $user->getTeamMembers();

        if ($teams->isEmpty()) {
            return $this->render('invoice/list.html.twig', [
                'invoices' => [],
            ]);
        }

        // Get invoices for all user's teams
        $invoices = [];
        foreach ($teams as $teamMember) {
            $team = $teamMember->getTeam();
            $subscription = $team->getSubscription();

            if ($subscription) {
                $teamInvoices = $this->invoiceRepository->findBySubscription($subscription);
                $invoices = array_merge($invoices, $teamInvoices);
            }
        }

        // Sort by date descending
        usort($invoices, function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        return $this->render('invoice/list.html.twig', [
            'invoices' => $invoices,
        ]);
    }
}
