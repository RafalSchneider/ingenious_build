<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_invoice_with_draft_status(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $this->assertEquals(StatusEnum::Draft, $invoice->status);
        $this->assertEquals('John Doe', $invoice->customer_name);
        $this->assertEquals('john@example.com', $invoice->customer_email);
        $this->assertNotNull($invoice->id);
    }

    public function test_creates_invoice_with_empty_product_lines(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $this->assertTrue($invoice->productLines->isEmpty());
        $this->assertEquals(0, $invoice->getTotalPrice());
    }

    public function test_calculates_total_price_with_single_product_line(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $this->assertEquals(200, $invoice->getTotalPrice());
    }

    public function test_calculates_total_price_with_multiple_product_lines(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]); // 200

        $invoice->productLines()->create([
            'name' => 'Product B',
            'quantity' => 3,
            'price' => 50
        ]); // 150

        $invoice->productLines()->create([
            'name' => 'Product C',
            'quantity' => 1,
            'price' => 300
        ]); // 300

        $invoice->refresh();

        $this->assertEquals(650, $invoice->getTotalPrice());
    }

    public function test_invoice_cannot_be_sent_when_status_is_not_draft(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Sending,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_without_product_lines(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_zero_quantity(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 0,
            'price' => 100
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_negative_quantity(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => -1,
            'price' => 100
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_zero_price(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 0
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_with_negative_price(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => -100
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_invoice_can_be_sent_when_all_conditions_are_met(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $this->assertTrue($invoice->canBeSent());
    }

    public function test_invoice_cannot_be_sent_if_any_product_line_has_invalid_values(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]); // Valid

        $invoice->productLines()->create([
            'name' => 'Product B',
            'quantity' => 0,
            'price' => 50
        ]); // Invalid (zero quantity)

        $invoice->productLines()->create([
            'name' => 'Product C',
            'quantity' => 1,
            'price' => 300
        ]); // Valid

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_marks_invoice_as_sending_when_conditions_are_met(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Sending, $invoice->fresh()->status);
    }

    public function test_does_not_mark_as_sending_when_status_is_not_draft(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::SentToClient,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->fresh()->status);
    }

    public function test_does_not_mark_as_sending_when_cannot_be_sent(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        // No product lines

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Draft, $invoice->fresh()->status);
    }

    public function test_marks_invoice_as_sent_to_client_when_status_is_sending(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Sending,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->fresh()->status);
    }

    public function test_does_not_mark_as_sent_to_client_when_status_is_draft(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::Draft, $invoice->fresh()->status);
    }

    public function test_does_not_change_status_when_already_sent_to_client(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::SentToClient,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->fresh()->status);
    }

    public function test_complete_workflow_from_draft_to_sent(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100
        ]);

        $invoice->refresh();

        // Initial state
        $this->assertEquals(StatusEnum::Draft, $invoice->status);
        $this->assertTrue($invoice->canBeSent());

        // Mark as sending
        $invoice->markAsSending();
        $this->assertEquals(StatusEnum::Sending, $invoice->fresh()->status);

        // Mark as sent to client
        $invoice->refresh();
        $invoice->markAsSentToClient();
        $this->assertEquals(StatusEnum::SentToClient, $invoice->fresh()->status);
    }
}
