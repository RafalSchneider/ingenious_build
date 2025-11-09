<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    public function test_creates_invoice_with_draft_status(): void
    {
        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com'
        );

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
        $this->assertEquals('John Doe', $invoice->getCustomerName());
        $this->assertEquals('john@example.com', $invoice->getCustomerEmail());
    }

    public function test_creates_invoice_with_empty_product_lines(): void
    {
        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: []
        );

        $this->assertEmpty($invoice->getProductLines());
        $this->assertEquals(0, $invoice->getTotalPrice());
    }

    public function test_calculates_total_price_with_single_product_line(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $this->assertEquals(200, $invoice->getTotalPrice());
    }

    public function test_calculates_total_price_with_multiple_product_lines(): void
    {
        $productLines = [
            new InvoiceProductLine('Product A', 2, 100),  // 200
            new InvoiceProductLine('Product B', 3, 50),   // 150
            new InvoiceProductLine('Product C', 1, 300),  // 300
        ];

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: $productLines
        );

        $this->assertEquals(650, $invoice->getTotalPrice());
    }

    public function test_invoice_cannot_be_sent_when_status_is_not_draft(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Sending,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_without_product_lines(): void
    {
        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: []
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_zero_quantity(): void
    {
        $productLine = new InvoiceProductLine('Product A', 0, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_negative_quantity(): void
    {
        $productLine = new InvoiceProductLine('Product A', -1, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_zero_price(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 0);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_negative_price(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, -100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_can_be_sent_when_all_conditions_are_met(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $this->assertTrue($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_if_any_product_line_has_invalid_values(): void
    {
        $productLines = [
            new InvoiceProductLine('Product A', 2, 100),  // Valid
            new InvoiceProductLine('Product B', 0, 50),   // Invalid (zero quantity)
            new InvoiceProductLine('Product C', 1, 300),  // Valid
        ];

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: $productLines
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_marks_invoice_as_sending_when_conditions_are_met(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }

    public function test_does_not_mark_as_sending_when_status_is_not_draft(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::SentToClient,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }

    public function test_does_not_mark_as_sending_when_cannot_be_sent(): void
    {
        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: []
        );

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
    }

    public function test_marks_invoice_as_sent_to_client_when_status_is_sending(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Sending,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }

    public function test_does_not_mark_as_sent_to_client_when_status_is_draft(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
    }

    public function test_does_not_change_status_when_already_sent_to_client(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::SentToClient,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }

    public function test_complete_workflow_from_draft_to_sent(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: '123',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [$productLine]
        );

        // Initial state
        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
        $this->assertTrue($invoice->canBeSent());

        // Mark as sending
        $invoice->markAsSending();
        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());

        // Mark as sent to client
        $invoice->markAsSentToClient();
        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }
}
