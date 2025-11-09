<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices;

use App\Models\Invoice;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\TestCase;
use Mockery;

class InvoiceServiceTest extends TestCase
{
    private InvoiceRepositoryInterface $invoiceRepository;
    private NotificationFacadeInterface $notificationFacade;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = Mockery::mock(NotificationFacadeInterface::class);
        $this->invoiceService = new InvoiceService(
            $this->invoiceRepository,
            $this->notificationFacade
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_invoice_with_draft_status(): void
    {
        $invoice = Mockery::mock(Invoice::class);
        $invoice->id = 'test-id';

        $this->invoiceRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($inv) use ($invoice) {
                $inv->id = 'test-id';
                return $invoice;
            });

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($invoice);

        $invoice->shouldReceive('productLines->create')->never();

        $result = $this->invoiceService->createInvoice('John Doe', 'john@example.com');

        $this->assertEquals('test-id', $result->id);
    }

    public function test_creates_invoice_with_product_lines(): void
    {
        $invoice = Mockery::mock(Invoice::class);
        $invoice->id = 'test-id';

        $productLineData = new InvoiceProductLine('Product A', 2, 100);

        $this->invoiceRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($inv) use ($invoice) {
                $inv->id = 'test-id';
                return $invoice;
            });

        $productLinesRelation = Mockery::mock();
        $productLinesRelation
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Product A',
                'price' => 100,
                'quantity' => 2,
            ]);

        $invoice->shouldReceive('productLines')->andReturn($productLinesRelation);

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($invoice);

        $result = $this->invoiceService->createInvoice(
            'John Doe',
            'john@example.com',
            [$productLineData]
        );

        $this->assertEquals('test-id', $result->id);
    }

    public function test_gets_invoice_by_id(): void
    {
        $invoice = Mockery::mock(Invoice::class);
        $invoice->id = 'test-id';

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($invoice);

        $result = $this->invoiceService->getInvoice('test-id');

        $this->assertEquals('test-id', $result->id);
    }

    public function test_returns_null_when_invoice_not_found(): void
    {
        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $result = $this->invoiceService->getInvoice('non-existent-id');

        $this->assertNull($result);
    }

    public function test_sends_invoice_when_conditions_are_met(): void
    {
        $invoice = Mockery::mock(Invoice::class);
        $invoice->id = 'test-id';
        $invoice->customer_email = 'john@example.com';
        $invoice->customer_name = 'John Doe';

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($invoice);

        $invoice->shouldReceive('canBeSent')->once()->andReturn(true);
        $invoice->shouldReceive('markAsSending')->once();
        $invoice->shouldReceive('getTotalPrice')->once()->andReturn(200);

        $this->invoiceRepository
            ->shouldReceive('save')
            ->once()
            ->with($invoice);

        $this->notificationFacade
            ->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(NotifyData::class));

        $result = $this->invoiceService->sendInvoice('test-id');

        $this->assertTrue($result);
    }

    public function test_does_not_send_invoice_when_not_found(): void
    {
        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn(null);

        $this->invoiceRepository->shouldReceive('save')->never();
        $this->notificationFacade->shouldReceive('notify')->never();

        $result = $this->invoiceService->sendInvoice('test-id');

        $this->assertFalse($result);
    }

    public function test_does_not_send_invoice_when_cannot_be_sent(): void
    {
        $invoice = Mockery::mock(Invoice::class);
        $invoice->id = 'test-id';

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($invoice);

        $invoice->shouldReceive('canBeSent')->once()->andReturn(false);
        $invoice->shouldReceive('markAsSending')->never();

        $this->invoiceRepository->shouldReceive('save')->never();
        $this->notificationFacade->shouldReceive('notify')->never();

        $result = $this->invoiceService->sendInvoice('test-id');

        $this->assertFalse($result);
    }

    public function test_notification_contains_correct_data(): void
    {
        $invoice = Mockery::mock(Invoice::class);
        $invoice->id = 'test-id-123';
        $invoice->customer_email = 'john@example.com';
        $invoice->customer_name = 'John Doe';

        $this->invoiceRepository
            ->shouldReceive('findById')
            ->once()
            ->with('test-id-123')
            ->andReturn($invoice);

        $invoice->shouldReceive('canBeSent')->once()->andReturn(true);
        $invoice->shouldReceive('markAsSending')->once();
        $invoice->shouldReceive('getTotalPrice')->once()->andReturn(650);

        $this->invoiceRepository
            ->shouldReceive('save')
            ->once()
            ->with($invoice);

        $this->notificationFacade
            ->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function ($notifyData) {
                return $notifyData instanceof NotifyData
                    && $notifyData->toEmail === 'john@example.com'
                    && $notifyData->subject === 'Your Invoice is Ready'
                    && str_contains($notifyData->message, 'John Doe')
                    && str_contains($notifyData->message, 'test-id-123')
                    && str_contains($notifyData->message, '650');
            }));

        $result = $this->invoiceService->sendInvoice('test-id-123');

        $this->assertTrue($result);
    }
}
