<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use PHPUnit\Framework\TestCase;

class InvoiceProductLineTest extends TestCase
{
    public function test_can_create_product_line(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product A',
            quantity: 5,
            price: 100
        );

        $this->assertEquals('Product A', $productLine->getName());
        $this->assertEquals(5, $productLine->getQuantity());
        $this->assertEquals(100, $productLine->getPrice());
    }

    public function test_calculates_total_unit_price_correctly(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product B',
            quantity: 3,
            price: 250
        );

        $this->assertEquals(750, $productLine->getTotalUnitPrice());
    }

    public function test_calculates_total_unit_price_with_quantity_one(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product C',
            quantity: 1,
            price: 500
        );

        $this->assertEquals(500, $productLine->getTotalUnitPrice());
    }

    public function test_calculates_total_unit_price_with_large_numbers(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product D',
            quantity: 100,
            price: 9999
        );

        $this->assertEquals(999900, $productLine->getTotalUnitPrice());
    }

    public function test_total_unit_price_is_zero_when_quantity_is_zero(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product E',
            quantity: 0,
            price: 100
        );

        $this->assertEquals(0, $productLine->getTotalUnitPrice());
    }

    public function test_total_unit_price_is_zero_when_price_is_zero(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product F',
            quantity: 10,
            price: 0
        );

        $this->assertEquals(0, $productLine->getTotalUnitPrice());
    }

    public function test_handles_negative_quantity(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product G',
            quantity: -5,
            price: 100
        );

        $this->assertEquals(-500, $productLine->getTotalUnitPrice());
    }

    public function test_handles_negative_price(): void
    {
        $productLine = new InvoiceProductLine(
            name: 'Product H',
            quantity: 5,
            price: -100
        );

        $this->assertEquals(-500, $productLine->getTotalUnitPrice());
    }
}
