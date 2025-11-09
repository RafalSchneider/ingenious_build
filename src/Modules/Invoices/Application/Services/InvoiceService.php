<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use App\Models\Invoice;
use App\Models\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Ramsey\Uuid\Uuid;

class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private NotificationFacadeInterface $notificationFacade
    ) {}

    public function createInvoice(string $customerName, string $customerEmail, array $productLinesData = []): Invoice
    {
        $invoice = new Invoice([
            'status' => StatusEnum::Draft,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
        ]);

        $this->invoiceRepository->save($invoice);

        // Create product lines if provided
        foreach ($productLinesData as $lineData) {
            $invoice->productLines()->create([
                'name' => $lineData['name'] ?? $lineData['productName'],
                'price' => $lineData['price'] ?? $lineData['unitPrice'],
                'quantity' => $lineData['quantity'],
            ]);
        }

        return $invoice->fresh('productLines');
    }

    public function getInvoice(string $id): ?Invoice
    {
        return $this->invoiceRepository->findById($id);
    }

    public function sendInvoice(string $id): bool
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice || !$invoice->canBeSent()) {
            return false;
        }

        $invoice->markAsSending();

        $notifyData = new NotifyData(
            resourceId: Uuid::fromString($invoice->id),
            toEmail: $invoice->customer_email,
            subject: 'Your Invoice is Ready',
            message: sprintf(
                'Dear %s, your invoice #%s is ready for review. Total amount: %d.',
                $invoice->customer_name,
                $invoice->id,
                $invoice->getTotalPrice()
            )
        );

        $this->notificationFacade->notify($notifyData);

        return true;
    }
}
