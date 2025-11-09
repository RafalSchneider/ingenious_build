<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Invoices\Application\Listeners;

use App\Models\Invoice;
use App\Models\InvoiceProductLine;
use Modules\Invoices\Application\Listeners\InvoiceDeliveredListener;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;

class InvoiceDeliveredListenerTest extends TestCase
{
    private InvoiceRepositoryInterface|MockObject $invoiceRepository;
    private InvoiceDeliveredListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        
        $this->listener = new InvoiceDeliveredListener($this->invoiceRepository);
    }

    public function test_updates_invoice_status_from_sending_to_sent_to_client(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = Uuid::fromString($invoiceId);

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Sending;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        // Create mock product lines to make canBeSent() return true
        $productLine = new InvoiceProductLine();
        $productLine->name = 'Product A';
        $productLine->quantity = 2;
        $productLine->price = 100;
        $invoice->setRelation('productLines', collect([$productLine]));

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);

        // Verify status changed to SentToClient
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);
    }

    public function test_does_not_update_invoice_when_not_found(): void
    {
        $invoiceId = 'non-existent-id';
        $uuid = Uuid::fromString($invoiceId);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn(null);

        $event = new ResourceDeliveredEvent($uuid);

        // Should not throw any exception
        $this->listener->handle($event);
        
        $this->assertTrue(true); // Assert we got here without errors
    }

    public function test_does_not_update_invoice_when_status_is_draft(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = Uuid::fromString($invoiceId);

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        $productLine = new InvoiceProductLine();
        $productLine->name = 'Product A';
        $productLine->quantity = 2;
        $productLine->price = 100;
        $invoice->setRelation('productLines', collect([$productLine]));

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);

        // Status should remain Draft
        $this->assertEquals(StatusEnum::Draft, $invoice->status);
    }

    public function test_does_not_update_invoice_when_already_sent_to_client(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = Uuid::fromString($invoiceId);

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::SentToClient;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        $productLine = new InvoiceProductLine();
        $productLine->name = 'Product A';
        $productLine->quantity = 2;
        $productLine->price = 100;
        $invoice->setRelation('productLines', collect([$productLine]));

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);

        // Status should remain SentToClient
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);
    }

    public function test_handles_multiple_events_for_same_invoice(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = Uuid::fromString($invoiceId);

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Sending;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        $productLine = new InvoiceProductLine();
        $productLine->name = 'Product A';
        $productLine->quantity = 2;
        $productLine->price = 100;
        $invoice->setRelation('productLines', collect([$productLine]));

        // First event
        $this->invoiceRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        // Handle first event - should update to SentToClient
        $this->listener->handle($event);
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);

        // Handle second event - should not change status again
        $this->listener->handle($event);
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);
    }
}
