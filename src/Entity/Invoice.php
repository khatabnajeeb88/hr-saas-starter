<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
class Invoice
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Payment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Payment $payment = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $billingName = null;

    #[ORM\Column(length: 255)]
    private ?string $billingEmail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taxId = null;

    #[ORM\Column(type: Types::JSON)]
    private array $lineItems = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $subtotal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $tax = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = 'USD';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(string $billingName): static
    {
        $this->billingName = $billingName;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(string $billingEmail): static
    {
        $this->billingEmail = $billingEmail;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): static
    {
        $this->taxId = $taxId;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function setLineItems(array $lineItems): static
    {
        $this->lineItems = $lineItems;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSubtotal(): ?string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getTax(): ?string
    {
        return $this->tax;
    }

    public function setTax(string $tax): static
    {
        $this->tax = $tax;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?\DateTimeImmutable $issuedAt): static
    {
        $this->issuedAt = $issuedAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get formatted total with currency
     */
    public function getFormattedTotal(): string
    {
        return $this->formatMoney($this->total, $this->currency);
    }

    /**
     * Get formatted subtotal with currency
     */
    public function getFormattedSubtotal(): string
    {
        return $this->formatMoney($this->subtotal, $this->currency);
    }

    /**
     * Get formatted tax with currency
     */
    public function getFormattedTax(): string
    {
        return $this->formatMoney($this->tax, $this->currency);
    }

    /**
     * Format money with currency symbol
     */
    private function formatMoney(string $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'SAR' => 'SAR ',
            'AED' => 'AED ',
            'KWD' => 'KWD ',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        $formatted = number_format((float) $amount, 2);

        return $symbol . $formatted;
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
