<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Ramsey\Uuid\Uuid;

class InvoiceService
{
    private InvoiceRepositoryInterface $invoiceRepository;
    private NotificationFacadeInterface $notificationFacade;

    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        NotificationFacadeInterface $notificationFacade
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->notificationFacade = $notificationFacade;
    }

    public function createInvoice(string $customerName, string $customerEmail, array $productLines = []): Invoice
    {
        $invoice = new Invoice(
            null, // ID będzie wygenerowany przez repozytorium
            'draft',
            $customerName,
            $customerEmail,
            $productLines
        );
        $this->invoiceRepository->save($invoice);
        return $invoice;
    }

    public function getInvoice(string $id): ?Invoice
    {
        return $this->invoiceRepository->findById($id);
    }

    public function sendInvoice(string $id): bool
    {
        $invoice = $this->invoiceRepository->findById($id);
        if ($invoice && $invoice->canBeSent()) {
            $invoice->markAsSending();
            $this->invoiceRepository->save($invoice);

            // Wysyłka notyfikacji email do klienta
            $notifyData = new NotifyData(
                resourceId: Uuid::fromString($invoice->getId()),
                toEmail: $invoice->getCustomerEmail(),
                subject: 'Your Invoice is Ready',
                message: sprintf(
                    'Dear %s, your invoice #%s is ready for review. Total amount: %d.',
                    $invoice->getCustomerName(),
                    $invoice->getId(),
                    $invoice->getTotalPrice()
                )
            );

            $this->notificationFacade->notify($notifyData);

            return true;
        }
        return false;
    }
}
