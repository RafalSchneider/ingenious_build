<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices;

use App\Models\Invoice;
use Modules\Invoices\Application\Listeners\InvoiceDeliveredListener;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Mockery;

class InvoiceDeliveredListenerTest extends TestCase
{
    private InvoiceRepositoryInterface $invoiceRepository;
    private InvoiceDeliveredListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $this->listener = new InvoiceDeliveredListener($this->invoiceRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_marks_invoice_as_sent_to_client_when_status_is_sending(): void
    {
        $uuid = Uuid::uuid4();
        $invoice = Mockery::mock(Invoice::class);
        $invoice->status = StatusEnum::Sending;

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with($uuid->toString())
            ->andReturn($invoice);

        $invoice->shouldReceive('markAsSentToClient')->once();

        $event = new ResourceDeliveredEvent($uuid);
        $this->listener->handle($event);
    }

    public function test_does_not_mark_invoice_when_status_is_not_sending(): void
    {
        $uuid = Uuid::uuid4();
        $invoice = Mockery::mock(Invoice::class);
        $invoice->status = StatusEnum::Draft;

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with($uuid->toString())
            ->andReturn($invoice);

        $invoice->shouldReceive('markAsSentToClient')->never();

        $event = new ResourceDeliveredEvent($uuid);
        $this->listener->handle($event);
    }

    public function test_does_not_mark_invoice_when_already_sent_to_client(): void
    {
        $uuid = Uuid::uuid4();
        $invoice = Mockery::mock(Invoice::class);
        $invoice->status = StatusEnum::SentToClient;

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with($uuid->toString())
            ->andReturn($invoice);

        $invoice->shouldReceive('markAsSentToClient')->never();

        $event = new ResourceDeliveredEvent($uuid);
        $this->listener->handle($event);
    }

    public function test_does_nothing_when_invoice_not_found(): void
    {
        $uuid = Uuid::uuid4();

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with($uuid->toString())
            ->andReturn(null);

        $event = new ResourceDeliveredEvent($uuid);
        $this->listener->handle($event);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }
}
