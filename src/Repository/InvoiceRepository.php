<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Find invoice by payment
     */
    public function findByPayment(Payment $payment): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.payment = :payment')
            ->setParameter('payment', $payment)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all invoices for a subscription
     *
     * @return Invoice[]
     */
    public function findBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.payment', 'p')
            ->where('p.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find invoice by invoice number
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.invoiceNumber = :invoiceNumber')
            ->setParameter('invoiceNumber', $invoiceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Find the last invoice number for this month
        $lastInvoice = $this->createQueryBuilder('i')
            ->where('i.invoiceNumber LIKE :prefix')
            ->setParameter('prefix', "INV-{$year}{$month}-%")
            ->orderBy('i.invoiceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastInvoice) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastInvoice->getInvoiceNumber());
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('INV-%s%s-%04d', $year, $month, $sequence);
    }

    /**
     * Get total revenue from paid invoices
     */
    public function getTotalRevenue(?string $currency = null): float
    {
        $qb = $this->createQueryBuilder('i')
            ->select('SUM(i.total) as total')
            ->where('i.status = :status')
            ->setParameter('status', Invoice::STATUS_PAID);

        if ($currency) {
            $qb->andWhere('i.currency = :currency')
               ->setParameter('currency', $currency);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
