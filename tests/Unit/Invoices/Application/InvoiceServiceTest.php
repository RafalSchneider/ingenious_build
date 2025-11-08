<?php

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class InvoiceServiceTest extends TestCase
{
    private InvoiceRepositoryInterface $invoiceRepository;
    private NotificationFacadeInterface $notificationFacade;
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

    public function test_create_invoice_creates_invoice_in_draft_status(): void
    {
        $customerName = 'John Doe';
        $customerEmail = 'john@example.com';
        $productLines = [];

        $this->invoiceRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($invoice) use ($customerName, $customerEmail) {
                return $invoice instanceof Invoice
                    && $invoice->getStatus() === 'draft'
                    && $invoice->getCustomerName() === $customerName
                    && $invoice->getCustomerEmail() === $customerEmail;
            }));

        $invoice = $this->invoiceService->createInvoice($customerName, $customerEmail, $productLines);

        $this->assertEquals('draft', $invoice->getStatus());
        $this->assertEquals($customerName, $invoice->getCustomerName());
        $this->assertEquals($customerEmail, $invoice->getCustomerEmail());
    }

    public function test_create_invoice_can_be_created_with_empty_product_lines(): void
    {
        $customerName = 'John Doe';
        $customerEmail = 'john@example.com';
        $productLines = [];

        $this->invoiceRepository
            ->expects($this->once())
            ->method('save');

        $invoice = $this->invoiceService->createInvoice($customerName, $customerEmail, $productLines);

        $this->assertEmpty($invoice->getProductLines());
    }

    public function test_get_invoice_returns_invoice_from_repository(): void
    {
        $invoiceId = Uuid::uuid4()->toString();
        $expectedInvoice = new Invoice(
            $invoiceId,
            'draft',
            'John Doe',
            'john@example.com',
            []
        );

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($expectedInvoice);

        $invoice = $this->invoiceService->getInvoice($invoiceId);

        $this->assertSame($expectedInvoice, $invoice);
    }

    public function test_get_invoice_returns_null_when_not_found(): void
    {
        $invoiceId = Uuid::uuid4()->toString();

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn(null);

        $invoice = $this->invoiceService->getInvoice($invoiceId);

        $this->assertNull($invoice);
    }

    public function test_send_invoice_returns_false_when_invoice_not_found(): void
    {
        $invoiceId = Uuid::uuid4()->toString();

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

    public function test_send_invoice_returns_false_when_invoice_cannot_be_sent(): void
    {
        $invoiceId = Uuid::uuid4()->toString();
        $invoice = new Invoice(
            $invoiceId,
            'draft',
            'John Doe',
            'john@example.com',
            [] // Empty product lines - cannot be sent
        );

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

    public function test_send_invoice_changes_status_to_sending_and_sends_notification(): void
    {
        $invoiceId = Uuid::uuid4()->toString();
        $productLine = new InvoiceProductLine('Product 1', 2, 100);
        $invoice = new Invoice(
            $invoiceId,
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine]
        );

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($savedInvoice) {
                return $savedInvoice->getStatus() === 'sending';
            }));

        $this->notificationFacade
            ->expects($this->once())
            ->method('notify')
            ->with($this->callback(function ($notifyData) use ($invoice) {
                return $notifyData instanceof NotifyData
                    && $notifyData->toEmail === 'john@example.com'
                    && str_contains($notifyData->subject, 'Invoice')
                    && str_contains($notifyData->message, 'John Doe');
            }));

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertTrue($result);
    }

    public function test_send_invoice_includes_total_price_in_notification_message(): void
    {
        $invoiceId = Uuid::uuid4()->toString();
        $productLine1 = new InvoiceProductLine('Product 1', 2, 100);
        $productLine2 = new InvoiceProductLine('Product 2', 3, 150);
        $invoice = new Invoice(
            $invoiceId,
            'draft',
            'John Doe',
            'john@example.com',
            [$productLine1, $productLine2]
        );

        $expectedTotalPrice = 650; // (2*100) + (3*150)

        $this->invoiceRepository
            ->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('save');

        $this->notificationFacade
            ->expects($this->once())
            ->method('notify')
            ->with($this->callback(function ($notifyData) use ($expectedTotalPrice) {
                return str_contains($notifyData->message, (string)$expectedTotalPrice);
            }));

        $result = $this->invoiceService->sendInvoice($invoiceId);

        $this->assertTrue($result);
    }
}
