<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceProductLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_is_created_with_uuid(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ]);

        $this->assertNotNull($invoice->id);
        $this->assertIsString($invoice->id);
        $this->assertEquals(36, strlen($invoice->id)); // UUID length
    }

    public function test_invoice_has_product_lines_relationship(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]);

        $invoice->productLines()->create([
            'name' => 'Product B',
            'quantity' => 1,
            'price' => 250,
        ]);

        $invoice->refresh();

        $this->assertCount(2, $invoice->productLines);
        $this->assertEquals('Product A', $invoice->productLines[0]->name);
        $this->assertEquals('Product B', $invoice->productLines[1]->name);
    }

    public function test_calculates_total_price_correctly(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]); // 200

        $invoice->productLines()->create([
            'name' => 'Product B',
            'quantity' => 3,
            'price' => 50,
        ]); // 150

        $invoice->productLines()->create([
            'name' => 'Product C',
            'quantity' => 1,
            'price' => 175,
        ]); // 175

        $invoice->refresh();

        $this->assertEquals(525, $invoice->getTotalPrice());
    }

    public function test_can_be_sent_when_all_conditions_are_met(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]);

        $invoice->refresh();

        $this->assertTrue($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_status_is_not_draft(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Sending,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_no_product_lines(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_quantity_is_zero(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 0,
            'price' => 100,
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_cannot_be_sent_when_price_is_zero(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 0,
        ]);

        $invoice->refresh();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_marks_as_sending_successfully(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]);

        $invoice->refresh();

        $this->assertEquals(StatusEnum::Draft, $invoice->status);

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Sending, $invoice->status);

        // Verify it's persisted to database
        $invoice->refresh();
        $this->assertEquals(StatusEnum::Sending, $invoice->status);
    }

    public function test_does_not_mark_as_sending_when_cannot_be_sent(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        // No product lines - cannot be sent
        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Draft, $invoice->status);
    }

    public function test_marks_as_sent_to_client_successfully(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Sending,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]);

        $invoice->refresh();

        $this->assertEquals(StatusEnum::Sending, $invoice->status);

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);

        // Verify it's persisted to database
        $invoice->refresh();
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);
    }

    public function test_does_not_mark_as_sent_to_client_when_status_is_draft(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]);

        $invoice->refresh();

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::Draft, $invoice->status);
    }

    public function test_complete_invoice_workflow(): void
    {
        // Create invoice in Draft status
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Workflow Test',
            'customer_email' => 'workflow@example.com',
        ]);

        $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 100,
        ]);

        $invoice->refresh();

        // Verify initial state
        $this->assertEquals(StatusEnum::Draft, $invoice->status);
        $this->assertTrue($invoice->canBeSent());

        // Mark as sending
        $invoice->markAsSending();
        $this->assertEquals(StatusEnum::Sending, $invoice->status);
        $this->assertFalse($invoice->canBeSent());

        // Mark as sent to client
        $invoice->markAsSentToClient();
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);

        // Verify final state persisted
        $invoice->refresh();
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);
    }

    public function test_product_line_calculates_total_unit_price(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $productLine = $invoice->productLines()->create([
            'name' => 'Product A',
            'quantity' => 5,
            'price' => 150,
        ]);

        $this->assertEquals(750, $productLine->getTotalUnitPrice());
    }

    public function test_invoice_status_enum_casting(): void
    {
        $invoice = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ]);

        $this->assertInstanceOf(StatusEnum::class, $invoice->status);
        $this->assertEquals(StatusEnum::Draft, $invoice->status);
        $this->assertEquals('draft', $invoice->status->value);
    }

    public function test_multiple_invoices_with_unique_ids(): void
    {
        $invoice1 = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'User 1',
            'customer_email' => 'user1@example.com',
        ]);

        $invoice2 = Invoice::create([
            'status' => StatusEnum::Draft,
            'customer_name' => 'User 2',
            'customer_email' => 'user2@example.com',
        ]);

        $this->assertNotEquals($invoice1->id, $invoice2->id);
    }
}
