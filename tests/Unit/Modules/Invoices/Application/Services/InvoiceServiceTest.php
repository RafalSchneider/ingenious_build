<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Invoices\Application\Services;

use App\Models\Invoice;
use App\Models\InvoiceProductLine;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class InvoiceServiceTest extends TestCase
{
    private InvoiceRepositoryInterface|MockObject $invoiceRepository;
    private NotificationFacadeInterface|MockObject $notificationFacade;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);

        $this->invoiceService = new InvoiceService(
            $this->invoiceRepository,
            $this->notificationFacade
        );
    }

    public function test_creates_invoice_in_draft_status(): void
    {
        $invoice = new Invoice();
        $invoice->id = '123e4567-e89b-12d3-a456-426614174000';
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'John Doe';
        $invoice->customer_email = 'john@example.com';

        $this->invoiceRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($inv) {
                return $inv instanceof Invoice
                    && $inv->status === StatusEnum::Draft
                    && $inv->customer_name === 'John Doe'
                    && $inv->customer_email === 'john@example.com';
            }))
            ->willReturnCallback(function ($inv) use ($invoice) {
                $inv->id = $invoice->id;
                return $invoice;
            });

        $result = $this->invoiceService->createInvoice(
            'John Doe',
            'john@example.com',
            []
        );

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals(StatusEnum::Draft, $result->status);
        $this->assertEquals('John Doe', $result->customer_name);
        $this->assertEquals('john@example.com', $result->customer_email);
    }

    public function test_creates_invoice_with_product_lines(): void
    {
        $invoice = new Invoice();
        $invoice->id = '123e4567-e89b-12d3-a456-426614174000';
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'Jane Smith';
        $invoice->customer_email = 'jane@example.com';

        $productLine1 = new \Modules\Invoices\Domain\Entities\InvoiceProductLine('Product A', 2, 100);
        $productLine2 = new \Modules\Invoices\Domain\Entities\InvoiceProductLine('Product B', 1, 250);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($inv) use ($invoice) {
                $inv->id = $invoice->id;
                return $invoice;
            });

        $result = $this->invoiceService->createInvoice(
            'Jane Smith',
            'jane@example.com',
            [$productLine1, $productLine2]
        );

        $this->assertInstanceOf(Invoice::class, $result);
    }

    public function test_gets_invoice_by_id(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $result = $this->invoiceService->getInvoice($invoiceId);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals($invoiceId, $result->id);
    }

    public function test_returns_null_when_invoice_not_found(): void
    {
        $invoiceId = 'non-existent-id';

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn(null);

        $result = $this->invoiceService->getInvoice($invoiceId);

        $this->assertNull($result);
    }

    public function test_sends_invoice_successfully(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        // Mock product lines relationship
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

        $this->invoiceRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($inv) {
                return $inv instanceof Invoice && $inv->status === StatusEnum::Sending;
            }));

        $this->notificationFacade
            ->expects($this->once())
            ->method('notify')
            ->with($this->callback(function ($notifyData) use ($invoiceId) {
                return $notifyData instanceof NotifyData
                    && $notifyData->resourceId->toString() === $invoiceId
                    && $notifyData->toEmail === 'test@example.com'
                    && str_contains($notifyData->subject, 'Invoice')
                    && str_contains($notifyData->message, 'Test User');
            }));

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertTrue($result);
    }

    public function test_does_not_send_invoice_when_not_found(): void
    {
        $invoiceId = 'non-existent-id';

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn(null);

        $this->invoiceRepository
            ->expects($this->never())
            ->method('save');

        $this->notificationFacade
            ->expects($this->never())
            ->method('notify');

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertFalse($result);
    }

    public function test_does_not_send_invoice_when_cannot_be_sent(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';

        // Invoice without product lines cannot be sent
        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';
        $invoice->setRelation('productLines', collect([]));

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->invoiceRepository
            ->expects($this->never())
            ->method('save');

        $this->notificationFacade
            ->expects($this->never())
            ->method('notify');

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertFalse($result);
    }

    public function test_does_not_send_invoice_when_status_is_not_draft(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Sending; // Not Draft
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

        $this->invoiceRepository
            ->expects($this->never())
            ->method('save');

        $this->notificationFacade
            ->expects($this->never())
            ->method('notify');

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertFalse($result);
    }

    public function test_does_not_send_invoice_when_product_lines_have_zero_quantity(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        $productLine = new InvoiceProductLine();
        $productLine->name = 'Product A';
        $productLine->quantity = 0; // Invalid
        $productLine->price = 100;

        $invoice->setRelation('productLines', collect([$productLine]));

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->invoiceRepository
            ->expects($this->never())
            ->method('save');

        $this->notificationFacade
            ->expects($this->never())
            ->method('notify');

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertFalse($result);
    }

    public function test_does_not_send_invoice_when_product_lines_have_zero_price(): void
    {
        $invoiceId = '123e4567-e89b-12d3-a456-426614174000';

        $invoice = new Invoice();
        $invoice->id = $invoiceId;
        $invoice->status = StatusEnum::Draft;
        $invoice->customer_name = 'Test User';
        $invoice->customer_email = 'test@example.com';

        $productLine = new InvoiceProductLine();
        $productLine->name = 'Product A';
        $productLine->quantity = 2;
        $productLine->price = 0; // Invalid

        $invoice->setRelation('productLines', collect([$productLine]));

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->invoiceRepository
            ->expects($this->never())
            ->method('save');

        $this->notificationFacade
            ->expects($this->never())
            ->method('notify');

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertFalse($result);
    }
}
