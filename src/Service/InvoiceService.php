<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class InvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceRepository $invoiceRepository,
        private Environment $twig,
        private LoggerInterface $logger,
        private string $projectDir,
    ) {
    }

    /**
     * Create invoice for payment
     */
    public function createInvoiceForPayment(Payment $payment): Invoice
    {
        // Check if invoice already exists
        $existingInvoice = $this->invoiceRepository->findByPayment($payment);
        if ($existingInvoice) {
            return $existingInvoice;
        }

        $subscription = $payment->getSubscription();
        $team = $subscription->getTeam();
        $owner = $team->getOwner();
        $plan = $subscription->getPlan();

        $invoice = new Invoice();
        $invoice->setPayment($payment);
        $invoice->setInvoiceNumber($this->invoiceRepository->generateInvoiceNumber());
        $invoice->setBillingName($team->getName());
        $invoice->setBillingEmail($owner->getEmail());
        $invoice->setCurrency($payment->getCurrency());

        // Create line items
        $lineItems = [
            [
                'description' => sprintf('%s - %s Subscription', $plan->getName(), ucfirst($plan->getBillingInterval())),
                'quantity' => 1,
                'unit_price' => $payment->getAmount(),
                'total' => $payment->getAmount(),
            ],
        ];

        $invoice->setLineItems($lineItems);
        $invoice->setSubtotal($payment->getAmount());
        $invoice->setTax('0.00'); // TODO: Implement tax calculation
        $invoice->setTotal($payment->getAmount());
        $invoice->setStatus(Invoice::STATUS_PAID);
        $invoice->setIssuedAt(new \DateTimeImmutable());

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        // Generate PDF
        $this->generatePDF($invoice);

        $this->logger->info('Invoice created', [
            'invoice_id' => $invoice->getId(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'payment_id' => $payment->getId(),
        ]);

        return $invoice;
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePDF(Invoice $invoice): string
    {
        try {
            // Configure Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);

            // Render HTML from template
            $html = $this->twig->render('invoice/pdf.html.twig', [
                'invoice' => $invoice,
            ]);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Save PDF
            $pdfDir = $this->projectDir . '/var/invoices';
            $filesystem = new Filesystem();
            $filesystem->mkdir($pdfDir);

            $filename = sprintf('invoice-%s.pdf', $invoice->getInvoiceNumber());
            $filepath = $pdfDir . '/' . $filename;

            file_put_contents($filepath, $dompdf->output());

            // Update invoice with PDF path
            $invoice->setPdfPath($filepath);
            $this->entityManager->flush();

            $this->logger->info('Invoice PDF generated', [
                'invoice_id' => $invoice->getId(),
                'pdf_path' => $filepath,
            ]);

            return $filepath;
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate invoice PDF', [
                'invoice_id' => $invoice->getId(),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to generate invoice PDF: ' . $e->getMessage());
        }
    }

    /**
     * Send invoice email
     */
    public function sendInvoiceEmail(Invoice $invoice, PaymentNotificationService $notificationService): void
    {
        // This would be integrated with PaymentNotificationService
        // For now, we'll add this to the payment success email
        $this->logger->info('Invoice email would be sent', [
            'invoice_id' => $invoice->getId(),
        ]);
    }

    /**
     * Get invoice download URL
     */
    public function getDownloadPath(Invoice $invoice): ?string
    {
        return $invoice->getPdfPath();
    }
}
