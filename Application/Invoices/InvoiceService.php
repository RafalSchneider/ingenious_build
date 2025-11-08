<?php

namespace Application\Invoices;

use Domain\Invoices\Entities\Invoice;
use Domain\Invoices\Entities\InvoiceProductLine;
use Domain\Invoices\Repositories\InvoiceRepositoryInterface;

class InvoiceService
{
    private InvoiceRepositoryInterface $invoiceRepository;

    public function __construct(InvoiceRepositoryInterface $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function createInvoice(string $customerName, string $customerEmail, array $productLines = []): Invoice
    {
        $invoice = new Invoice(
            0, // ID generowany automatycznie
            status: 'draft',
            $customerName,
            $customerEmail,
            $productLines
        );
        $this->invoiceRepository->save($invoice);
        return $invoice;
    }

    public function getInvoice(int $id): ?Invoice
    {
        return $this->invoiceRepository->findById($id);
    }

    public function sendInvoice(int $id): bool
    {
        $invoice = $this->invoiceRepository->findById($id);
        if ($invoice && $invoice->canBeSent()) {
            $invoice->markAsSending();
            $this->invoiceRepository->save($invoice);
            // Tu wywołanie NotificationFacade
            return true;
        }
        return false;
    }
}
