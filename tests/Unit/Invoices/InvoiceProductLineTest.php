<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use PHPUnit\Framework\TestCase;

class InvoiceProductLineTest extends TestCase
{
    public function test_creates_product_line_with_valid_data(): void
    {
        $line = new InvoiceProductLine('Product A', 5, 100);

        $this->assertEquals('Product A', $line->getName());
        $this->assertEquals(5, $line->getQuantity());
        $this->assertEquals(100, $line->getPrice());
    }

    public function test_calculates_total_unit_price_correctly(): void
    {
        $line = new InvoiceProductLine('Product A', 5, 100);

        $this->assertEquals(500, $line->getTotalUnitPrice());
    }

    public function test_calculates_total_unit_price_with_single_quantity(): void
    {
        $line = new InvoiceProductLine('Product B', 1, 250);

        $this->assertEquals(250, $line->getTotalUnitPrice());
    }

    public function test_calculates_total_unit_price_with_large_quantity(): void
    {
        $line = new InvoiceProductLine('Product C', 1000, 50);

        $this->assertEquals(50000, $line->getTotalUnitPrice());
    }

    public function test_handles_zero_quantity(): void
    {
        $line = new InvoiceProductLine('Product D', 0, 100);

        $this->assertEquals(0, $line->getTotalUnitPrice());
    }

    public function test_handles_zero_price(): void
    {
        $line = new InvoiceProductLine('Product E', 5, 0);

        $this->assertEquals(0, $line->getTotalUnitPrice());
    }
}
