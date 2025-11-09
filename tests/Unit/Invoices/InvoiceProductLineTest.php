<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices;

use App\Models\InvoiceProductLine;
use Tests\TestCase;

class InvoiceProductLineTest extends TestCase
{
    public function test_creates_product_line_with_valid_data(): void
    {
        $line = new InvoiceProductLine([
            'name' => 'Product A',
            'quantity' => 5,
            'price' => 100
        ]);

        $this->assertEquals('Product A', $line->name);
        $this->assertEquals(5, $line->quantity);
        $this->assertEquals(100, $line->price);
    }

    public function test_calculates_total_unit_price_correctly(): void
    {
        $line = new InvoiceProductLine([
            'name' => 'Product A',
            'quantity' => 5,
            'price' => 100
        ]);

        $this->assertEquals(500, $line->getTotalUnitPrice());
    }

    public function test_calculates_total_unit_price_with_single_quantity(): void
    {
        $line = new InvoiceProductLine([
            'name' => 'Product B',
            'quantity' => 1,
            'price' => 250
        ]);

        $this->assertEquals(250, $line->getTotalUnitPrice());
    }

    public function test_calculates_total_unit_price_with_large_quantity(): void
    {
        $line = new InvoiceProductLine([
            'name' => 'Product C',
            'quantity' => 1000,
            'price' => 50
        ]);

        $this->assertEquals(50000, $line->getTotalUnitPrice());
    }

    public function test_handles_zero_quantity(): void
    {
        $line = new InvoiceProductLine([
            'name' => 'Product D',
            'quantity' => 0,
            'price' => 100
        ]);

        $this->assertEquals(0, $line->getTotalUnitPrice());
    }

    public function test_handles_zero_price(): void
    {
        $line = new InvoiceProductLine([
            'name' => 'Product E',
            'quantity' => 5,
            'price' => 0
        ]);

        $this->assertEquals(0, $line->getTotalUnitPrice());
    }
}
