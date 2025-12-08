<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    public function testFormatMoneyUsd()
    {
        $invoice = new Invoice();
        $invoice->setTotal('10.00');
        $invoice->setCurrency('USD');

        $this->assertEquals('$10.00', $invoice->getFormattedTotal());
    }

    public function testFormatMoneyEur()
    {
        $invoice = new Invoice();
        $invoice->setTotal('10.00');
        $invoice->setCurrency('EUR');

        $this->assertEquals('€10.00', $invoice->getFormattedTotal());
    }

    public function testFormatMoneySar()
    {
        $invoice = new Invoice();
        $invoice->setTotal('100.50');
        $invoice->setCurrency('SAR');

        $this->assertEquals('SAR 100.50', $invoice->getFormattedTotal());
    }

    public function testInvoiceStatusConstants()
    {
        $this->assertEquals('draft', Invoice::STATUS_DRAFT);
        $this->assertEquals('sent', Invoice::STATUS_SENT);
        $this->assertEquals('paid', Invoice::STATUS_PAID);
        $this->assertEquals('void', Invoice::STATUS_VOID);
    }
}
