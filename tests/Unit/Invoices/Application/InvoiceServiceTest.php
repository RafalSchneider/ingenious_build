<?php<?php<?php



namespace Tests\Unit\Invoices\Application;



use App\Models\Invoice;namespace Tests\Unit\Invoices\Application;namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Services\InvoiceService;

use Modules\Invoices\Domain\Enums\StatusEnum;

use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;

use Modules\Notifications\Api\NotificationFacadeInterface;use App\Models\Invoice;use App\Models\Invoice;

use Modules\Notifications\Api\Dtos\NotifyData;

use PHPUnit\Framework\TestCase;use Modules\Invoices\Application\Services\InvoiceService;use Modules\Invoices\Application\Services\InvoiceService;

use Ramsey\Uuid\Uuid;

use Mockery;use Modules\Invoices\Domain\Enums\StatusEnum;use Modules\Invoices\Domain\Enums\StatusEnum;



class InvoiceServiceTest extends TestCaseuse Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;

{

    private InvoiceRepositoryInterface $invoiceRepository;use Modules\Notifications\Api\NotificationFacadeInterface;use Modules\Notifications\Api\NotificationFacadeInterface;

    private NotificationFacadeInterface $notificationFacade;

    private InvoiceService $invoiceService;use Modules\Notifications\Api\Dtos\NotifyData;use Modules\Notifications\Api\Dtos\NotifyData;



    protected function setUp(): voiduse PHPUnit\Framework\TestCase;use PHPUnit\Framework\TestCase;

    {

        parent::setUp();use Ramsey\Uuid\Uuid;use Ramsey\Uuid\Uuid;



        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);use Mockery;use Mockery;

        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);

        $this->invoiceService = new InvoiceService(

            $this->invoiceRepository,

            $this->notificationFacadeclass InvoiceServiceTest extends TestCaseclass InvoiceServiceTest extends TestCase

        );

    }{{



    protected function tearDown(): void    private InvoiceRepositoryInterface $invoiceRepository;    private InvoiceRepositoryInterface $invoiceRepository;

    {

        Mockery::close();    private NotificationFacadeInterface $notificationFacade;    private NotificationFacadeInterface $notificationFacade;

        parent::tearDown();

    }    private InvoiceService $invoiceService;    private InvoiceService $invoiceService;



    public function test_create_invoice_creates_invoice_in_draft_status(): void

    {

        $customerName = 'John Doe';    protected function setUp(): void    protected function setUp(): void

        $customerEmail = 'john@example.com';

        $productLines = [];    {    {



        $this->invoiceRepository        parent::setUp();        parent::setUp();

            ->expects($this->once())

            ->method('save');



        $invoice = $this->invoiceService->createInvoice($customerName, $customerEmail, $productLines);        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);



        $this->assertEquals(StatusEnum::Draft, $invoice->status);        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);

        $this->assertEquals($customerName, $invoice->customer_name);

        $this->assertEquals($customerEmail, $invoice->customer_email);        $this->invoiceService = new InvoiceService(        $this->invoiceService = new InvoiceService(

    }

            $this->invoiceRepository,            $this->invoiceRepository,

    public function test_get_invoice_returns_invoice_from_repository(): void

    {            $this->notificationFacade            $this->notificationFacade

        $invoiceId = Uuid::uuid4()->toString();

        $expectedInvoice = Mockery::mock(Invoice::class);        );        );



        $this->invoiceRepository    }    }

            ->expects($this->once())

            ->method('findById')

            ->with($invoiceId)

            ->willReturn($expectedInvoice);    protected function tearDown(): void    protected function tearDown(): void



        $invoice = $this->invoiceService->getInvoice($invoiceId);    {    {



        $this->assertSame($expectedInvoice, $invoice);        Mockery::close();        Mockery::close();

    }

        parent::tearDown();        parent::tearDown();

    public function test_get_invoice_returns_null_when_not_found(): void

    {    }    }

        $invoiceId = Uuid::uuid4()->toString();



        $this->invoiceRepository

            ->expects($this->once())    public function test_create_invoice_creates_invoice_in_draft_status(): void    public function test_create_invoice_creates_invoice_in_draft_status(): void

            ->method('findById')

            ->with($invoiceId)    {    {

            ->willReturn(null);

        $customerName = 'John Doe';        $customerName = 'John Doe';

        $invoice = $this->invoiceService->getInvoice($invoiceId);

        $customerEmail = 'john@example.com';        $customerEmail = 'john@example.com';

        $this->assertNull($invoice);

    }        $productLines = [];        $productLines = [];



    public function test_send_invoice_returns_false_when_invoice_not_found(): void

    {

        $invoiceId = Uuid::uuid4()->toString();        $this->invoiceRepository        $this->invoiceRepository



        $this->invoiceRepository            ->expects($this->once())            ->expects($this->once())

            ->expects($this->once())

            ->method('findById')            ->method('save');            ->method('save');

            ->with($invoiceId)

            ->willReturn(null);



        $this->notificationFacade        $invoice = $this->invoiceService->createInvoice($customerName, $customerEmail, $productLines);        $invoice = $this->invoiceService->createInvoice($customerName, $customerEmail, $productLines);

            ->expects($this->never())

            ->method('notify');



        $result = $this->invoiceService->sendInvoice($invoiceId);        $this->assertEquals(StatusEnum::Draft, $invoice->status);        $this->assertEquals(StatusEnum::Draft, $invoice->status);



        $this->assertFalse($result);        $this->assertEquals($customerName, $invoice->customer_name);        $this->assertEquals($customerName, $invoice->customer_name);

    }

        $this->assertEquals($customerEmail, $invoice->customer_email);        $this->assertEquals($customerEmail, $invoice->customer_email);

    public function test_send_invoice_returns_false_when_invoice_cannot_be_sent(): void

    {    }    }

        $invoiceId = Uuid::uuid4()->toString();

        

        $invoice = Mockery::mock(Invoice::class);

        $invoice->shouldReceive('canBeSent')->andReturn(false);    public function test_get_invoice_returns_invoice_from_repository(): void    public function test_get_invoice_returns_invoice_from_repository(): void



        $this->invoiceRepository    {    {

            ->expects($this->once())

            ->method('findById')        $invoiceId = Uuid::uuid4()->toString();        $invoiceId = Uuid::uuid4()->toString();

            ->with($invoiceId)

            ->willReturn($invoice);        $expectedInvoice = Mockery::mock(Invoice::class);        $expectedInvoice = Mockery::mock(Invoice::class);



        $this->notificationFacade

            ->expects($this->never())

            ->method('notify');        $this->invoiceRepository        $this->invoiceRepository



        $result = $this->invoiceService->sendInvoice($invoiceId);            ->expects($this->once())            ->expects($this->once())



        $this->assertFalse($result);            ->method('findById')            ->method('findById')

    }

            ->with($invoiceId)            ->with($invoiceId)

    public function test_send_invoice_changes_status_to_sending_and_sends_notification(): void

    {            ->willReturn($expectedInvoice);            ->willReturn($expectedInvoice);

        $invoiceId = Uuid::uuid4()->toString();

        

        $invoice = Mockery::mock(Invoice::class);

        $invoice->id = $invoiceId;        $invoice = $this->invoiceService->getInvoice($invoiceId);        $invoice = $this->invoiceService->getInvoice($invoiceId);

        $invoice->customer_email = 'john@example.com';

        $invoice->customer_name = 'John Doe';

        

        $invoice->shouldReceive('canBeSent')->andReturn(true);        $this->assertSame($expectedInvoice, $invoice);        $this->assertSame($expectedInvoice, $invoice);

        $invoice->shouldReceive('markAsSending')->once();

        $invoice->shouldReceive('getTotalPrice')->andReturn(200);    }    }



        $this->invoiceRepository

            ->expects($this->once())

            ->method('findById')    public function test_get_invoice_returns_null_when_not_found(): void    public function test_get_invoice_returns_null_when_not_found(): void

            ->with($invoiceId)

            ->willReturn($invoice);    {    {



        $this->notificationFacade        $invoiceId = Uuid::uuid4()->toString();        $invoiceId = Uuid::uuid4()->toString();

            ->expects($this->once())

            ->method('notify')

            ->with($this->callback(function ($notifyData) {

                return $notifyData instanceof NotifyData        $this->invoiceRepository        $this->invoiceRepository

                    && $notifyData->toEmail === 'john@example.com'

                    && str_contains($notifyData->subject, 'Invoice')            ->expects($this->once())            ->expects($this->once())

                    && str_contains($notifyData->message, 'John Doe');

            }));            ->method('findById')            ->method('findById')



        $result = $this->invoiceService->sendInvoice($invoiceId);            ->with($invoiceId)            ->with($invoiceId)



        $this->assertTrue($result);            ->willReturn(null);            ->willReturn(null);

    }

}


        $invoice = $this->invoiceService->getInvoice($invoiceId);        $invoice = $this->invoiceService->getInvoice($invoiceId);



        $this->assertNull($invoice);        $this->assertNull($invoice);

    }    }



    public function test_send_invoice_returns_false_when_invoice_not_found(): void    public function test_send_invoice_returns_false_when_invoice_not_found(): void

    {    {

        $invoiceId = Uuid::uuid4()->toString();        $invoiceId = Uuid::uuid4()->toString();



        $this->invoiceRepository        $this->invoiceRepository

            ->expects($this->once())            ->expects($this->once())

            ->method('findById')            ->method('findById')

            ->with($invoiceId)            ->with($invoiceId)

            ->willReturn(null);            ->willReturn(null);



        $this->notificationFacade        $this->notificationFacade

            ->expects($this->never())            ->expects($this->never())

            ->method('notify');            ->method('notify');



        $result = $this->invoiceService->sendInvoice($invoiceId);        $result = $this->invoiceService->sendInvoice($invoiceId);



        $this->assertFalse($result);        $this->assertFalse($result);

    }    }



    public function test_send_invoice_returns_false_when_invoice_cannot_be_sent(): void    public function test_send_invoice_returns_false_when_invoice_cannot_be_sent(): void

    {    {

        $invoiceId = Uuid::uuid4()->toString();        $invoiceId = Uuid::uuid4()->toString();

                

        $invoice = Mockery::mock(Invoice::class);        $invoice = Mockery::mock(Invoice::class);

        $invoice->shouldReceive('canBeSent')->andReturn(false);        $invoice->shouldReceive('canBeSent')->andReturn(false);



        $this->invoiceRepository        $this->invoiceRepository

            ->expects($this->once())            ->expects($this->once())

            ->method('findById')            ->method('findById')

            ->with($invoiceId)            ->with($invoiceId)

            ->willReturn($invoice);            ->willReturn($invoice);



        $this->notificationFacade        $this->notificationFacade

            ->expects($this->never())            ->expects($this->never())

            ->method('notify');            ->method('notify');



        $result = $this->invoiceService->sendInvoice($invoiceId);        $result = $this->invoiceService->sendInvoice($invoiceId);



        $this->assertFalse($result);        $this->assertFalse($result);

    }    }



    public function test_send_invoice_changes_status_to_sending_and_sends_notification(): void    public function test_send_invoice_changes_status_to_sending_and_sends_notification(): void

    {    {

        $invoiceId = Uuid::uuid4()->toString();        $invoiceId = Uuid::uuid4()->toString();

                

        $invoice = Mockery::mock(Invoice::class);        $invoice = Mockery::mock(Invoice::class);

        $invoice->id = $invoiceId;        $invoice->id = $invoiceId;

        $invoice->customer_email = 'john@example.com';        $invoice->customer_email = 'john@example.com';

        $invoice->customer_name = 'John Doe';        $invoice->customer_name = 'John Doe';

                

        $invoice->shouldReceive('canBeSent')->andReturn(true);        $invoice->shouldReceive('canBeSent')->andReturn(true);

        $invoice->shouldReceive('markAsSending')->once();        $invoice->shouldReceive('markAsSending')->once();

        $invoice->shouldReceive('getTotalPrice')->andReturn(200);        $invoice->shouldReceive('getTotalPrice')->andReturn(200);



        $this->invoiceRepository        $this->invoiceRepository

            ->expects($this->once())            ->expects($this->once())

            ->method('findById')            ->method('findById')

            ->with($invoiceId)            ->with($invoiceId)

            ->willReturn($invoice);            ->willReturn($invoice);



        $this->notificationFacade        $this->notificationFacade

            ->expects($this->once())            ->expects($this->once())

            ->method('notify')            ->method('notify')

            ->with($this->callback(function ($notifyData) {            ->with($this->callback(function ($notifyData) {

                return $notifyData instanceof NotifyData                return $notifyData instanceof NotifyData

                    && $notifyData->toEmail === 'john@example.com'                    && $notifyData->toEmail === 'john@example.com'

                    && str_contains($notifyData->subject, 'Invoice')                    && str_contains($notifyData->subject, 'Invoice')

                    && str_contains($notifyData->message, 'John Doe');                    && str_contains($notifyData->message, 'John Doe');

            }));            }));



        $result = $this->invoiceService->sendInvoice($invoiceId);        $result = $this->invoiceService->sendInvoice($invoiceId);



        $this->assertTrue($result);        $this->assertTrue($result);

    }    }

}}



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
                    && $invoice->getStatus() === StatusEnum::Draft
                    && $invoice->getCustomerName() === $customerName
                    && $invoice->getCustomerEmail() === $customerEmail;
            }));

        $invoice = $this->invoiceService->createInvoice($customerName, $customerEmail, $productLines);

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
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
            StatusEnum::Draft,
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
            StatusEnum::Draft,
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
            StatusEnum::Draft,
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
                return $savedInvoice->getStatus() === StatusEnum::Sending;
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
            StatusEnum::Draft,
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
