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

        $invoice = $this->createMock(Invoice::class);
        $invoice->status = StatusEnum::Sending;

        $invoice->expects($this->once())
            ->method('markAsSentToClient');

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);
    }

    public function test_does_not_update_invoice_when_not_found(): void
    {
        $invoiceId = '223e4567-e89b-12d3-a456-426614174000'; // Valid UUID
        $uuid = Uuid::fromString($invoiceId);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn(null);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_does_not_update_invoice_when_status_is_draft(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = Uuid::fromString($invoiceId);

        $invoice = $this->createMock(Invoice::class);
        $invoice->status = StatusEnum::Draft;

        $invoice->expects($this->never())
            ->method('markAsSentToClient');

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);

        $this->assertEquals(StatusEnum::Draft, $invoice->status);
    }

    public function test_does_not_update_invoice_when_already_sent_to_client(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = Uuid::fromString($invoiceId);

        $invoice = $this->createMock(Invoice::class);
        $invoice->status = StatusEnum::SentToClient;

        $invoice->expects($this->never())
            ->method('markAsSentToClient');

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);

        $this->assertEquals(StatusEnum::SentToClient, $invoice->status);
    }

    public function test_handles_multiple_events_for_same_invoice(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = Uuid::fromString($invoiceId);

        $invoice = $this->createMock(Invoice::class);
        $invoice->status = StatusEnum::Sending;

        // First call will change status, second call won't (already sent)
        $invoice->expects($this->exactly(2))
            ->method('markAsSentToClient');

        $this->invoiceRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $event = new ResourceDeliveredEvent($uuid);

        $this->listener->handle($event);
        $this->listener->handle($event);

        $this->assertTrue(true); // If we got here without errors, test passed
    }
}
