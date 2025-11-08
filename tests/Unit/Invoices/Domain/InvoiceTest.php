<?php

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    public function test_invoice_can_be_created_in_draft_status(): void
    {
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            []
        );

        $this->assertEquals('draft', $invoice->getStatus());
        $this->assertEquals('John Doe', $invoice->getCustomerName());
        $this->assertEquals('john@example.com', $invoice->getCustomerEmail());
        $this->assertEmpty($invoice->getProductLines());
    }

    public function test_invoice_can_be_created_with_empty_product_lines(): void
    {
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            []
        );

        $this->assertEmpty($invoice->getProductLines());
    }

    public function test_get_total_price_calculates_sum_of_all_product_lines(): void
    {
        $productLine1 = new InvoiceProductLine('Product 1', 2, 100);
        $productLine2 = new InvoiceProductLine('Product 2', 3, 150);

        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine1, $productLine2]
        );

        // (2 * 100) + (3 * 150) = 200 + 450 = 650
        $this->assertEquals(650, $invoice->getTotalPrice());
    }

    public function test_invoice_cannot_be_sent_if_status_is_not_draft(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 2, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'sending',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_if_no_product_lines(): void
    {
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            []
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_if_product_line_has_zero_quantity(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 0, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_if_product_line_has_negative_quantity(): void
    {
        $productLine = new InvoiceProductLine('Product 1', -5, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_if_product_line_has_zero_unit_price(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 5, 0);
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_if_product_line_has_negative_unit_price(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 5, -100);
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_can_be_sent_if_all_conditions_are_met(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 2, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $this->assertTrue($invoice->canBeSent());
    }

    public function test_mark_as_sending_changes_status_from_draft_to_sending(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 2, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $invoice->markAsSending();

        $this->assertEquals('sending', $invoice->getStatus());
    }

    public function test_mark_as_sending_does_not_change_status_if_invoice_cannot_be_sent(): void
    {
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [] // Empty product lines
        );

        $invoice->markAsSending();

        $this->assertEquals('draft', $invoice->getStatus());
    }

    public function test_mark_as_sending_does_not_change_status_if_not_in_draft(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 2, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'sent-to-client',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $invoice->markAsSending();

        $this->assertEquals('sent-to-client', $invoice->getStatus());
    }

    public function test_mark_as_sent_to_client_changes_status_from_sending_to_sent_to_client(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 2, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'sending',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $invoice->markAsSentToClient();

        $this->assertEquals('sent-to-client', $invoice->getStatus());
    }

    public function test_mark_as_sent_to_client_does_not_change_status_if_not_in_sending(): void
    {
        $productLine = new InvoiceProductLine('Product 1', 2, 100);
        $invoice = new Invoice(
            'test-uuid-123',
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $invoice->markAsSentToClient();

        $this->assertEquals('draft', $invoice->getStatus());
    }
}
