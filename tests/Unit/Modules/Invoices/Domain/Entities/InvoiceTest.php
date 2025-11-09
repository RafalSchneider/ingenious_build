<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    public function test_can_create_invoice_in_draft_status(): void
    {
        $invoice = new Invoice(
            id: '123e4567-e89b-12d3-a456-426614174000',
            status: StatusEnum::Draft,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: []
        );

        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $invoice->getId());
        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
        $this->assertEquals('John Doe', $invoice->getCustomerName());
        $this->assertEquals('john@example.com', $invoice->getCustomerEmail());
        $this->assertEmpty($invoice->getProductLines());
    }

    public function test_can_create_invoice_with_product_lines(): void
    {
        $productLine1 = new InvoiceProductLine('Product A', 2, 100);
        $productLine2 = new InvoiceProductLine('Product B', 1, 250);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Jane Smith',
            customerEmail: 'jane@example.com',
            productLines: [$productLine1, $productLine2]
        );

        $this->assertCount(2, $invoice->getProductLines());
        $this->assertEquals($productLine1, $invoice->getProductLines()[0]);
        $this->assertEquals($productLine2, $invoice->getProductLines()[1]);
    }

    public function test_calculates_total_price_correctly(): void
    {
        $productLine1 = new InvoiceProductLine('Product A', 2, 100);  // 200
        $productLine2 = new InvoiceProductLine('Product B', 3, 50);   // 150
        $productLine3 = new InvoiceProductLine('Product C', 1, 175);  // 175

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine1, $productLine2, $productLine3]
        );

        $this->assertEquals(525, $invoice->getTotalPrice());
    }

    public function test_calculates_zero_total_price_for_empty_product_lines(): void
    {
        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: []
        );

        $this->assertEquals(0, $invoice->getTotalPrice());
    }

    public function test_cannot_be_sent_when_status_is_not_draft(): void
    {
        $productLine = new InvoiceProductLine('Product A', 1, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Sending,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_product_lines_are_empty(): void
    {
        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: []
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_product_line_has_zero_quantity(): void
    {
        $productLine = new InvoiceProductLine('Product A', 0, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_product_line_has_negative_quantity(): void
    {
        $productLine = new InvoiceProductLine('Product A', -5, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_product_line_has_zero_price(): void
    {
        $productLine = new InvoiceProductLine('Product A', 5, 0);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_product_line_has_negative_price(): void
    {
        $productLine = new InvoiceProductLine('Product A', 5, -100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_any_product_line_is_invalid(): void
    {
        $validLine = new InvoiceProductLine('Product A', 2, 100);
        $invalidLine = new InvoiceProductLine('Product B', 0, 50);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$validLine, $invalidLine]
        );

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_can_be_sent_when_all_conditions_are_met(): void
    {
        $productLine1 = new InvoiceProductLine('Product A', 2, 100);
        $productLine2 = new InvoiceProductLine('Product B', 1, 250);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine1, $productLine2]
        );

        $this->assertTrue($invoice->canBeSent());
    }

    public function test_marks_as_sending_when_invoice_can_be_sent(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }

    public function test_does_not_mark_as_sending_when_invoice_cannot_be_sent(): void
    {
        // Invoice without product lines cannot be sent
        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: []
        );

        $invoice->markAsSending();

        // Status should remain Draft
        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
    }

    public function test_does_not_mark_as_sending_when_status_is_not_draft(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Sending,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSending();

        // Status should remain Sending
        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }

    public function test_marks_as_sent_to_client_when_status_is_sending(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Sending,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }

    public function test_does_not_mark_as_sent_to_client_when_status_is_draft(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSentToClient();

        // Status should remain Draft
        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
    }

    public function test_does_not_mark_as_sent_to_client_when_already_sent_to_client(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        $invoice = new Invoice(
            id: null,
            status: StatusEnum::SentToClient,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $invoice->markAsSentToClient();

        // Status should remain SentToClient
        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }

    public function test_complete_status_workflow(): void
    {
        $productLine = new InvoiceProductLine('Product A', 2, 100);

        // Start with Draft
        $invoice = new Invoice(
            id: null,
            status: StatusEnum::Draft,
            customerName: 'Test User',
            customerEmail: 'test@example.com',
            productLines: [$productLine]
        );

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
        $this->assertTrue($invoice->canBeSent());

        // Move to Sending
        $invoice->markAsSending();
        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
        $this->assertFalse($invoice->canBeSent()); // Can't send when not Draft

        // Move to SentToClient
        $invoice->markAsSentToClient();
        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }
}
